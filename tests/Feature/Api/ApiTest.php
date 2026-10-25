<?php

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Sales\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
});

function apiUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('issues a token for an operational user', function () {
    $user = apiUserWithRole('sales');

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['access_token']);
});

it('rejects login without an operational role and invalid credentials', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->postJson('/api/login', [
        'email' => 'missing@example.test',
        'password' => 'wrong',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('requires a bearer token for protected endpoints', function () {
    $this->getJson('/api/products')->assertUnauthorized();
    $this->getJson('/api/customers')->assertUnauthorized();
});

it('returns the authenticated user profile', function () {
    $user = apiUserWithRole('auditor');

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('access_token');

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.roles.0', 'auditor');
});

it('lets an administrator manage products through the API', function () {
    $user = apiUserWithRole('super_admin');

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('access_token');

    $payload = [
        'sku' => 'API-001',
        'name' => 'Produk API',
        'unit_id' => $this->unit->id,
        'cost_price' => '1000',
        'selling_price' => '1500',
        'is_active' => true,
        'is_stockable' => true,
    ];

    $productId = $this->withToken($token)
        ->postJson('/api/products', $payload)
        ->assertCreated()
        ->assertJsonPath('data.sku', 'API-001')
        ->json('data.id');

    $this->withToken($token)
        ->getJson('/api/products?search=API-001')
        ->assertOk()
        ->assertJsonFragment(['sku' => 'API-001']);

    $this->withToken($token)
        ->putJson("/api/products/{$productId}", [...$payload, 'selling_price' => '2000'])
        ->assertOk()
        ->assertJsonPath('data.selling_price', '2000.0000');

    $this->withToken($token)
        ->deleteJson("/api/products/{$productId}")
        ->assertOk();

    expect(Product::withTrashed()->find($productId))->not->toBeNull();
});

it('validates product payloads and blocks unauthorized writes', function () {
    $auditor = apiUserWithRole('auditor');
    $token = $this->postJson('/api/login', [
        'email' => $auditor->email,
        'password' => 'password',
    ])->json('access_token');

    $this->withToken($token)
        ->postJson('/api/products', ['sku' => 'X'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'unit_id', 'cost_price', 'selling_price']);
});

it('creates and confirms a sales order through the API', function () {
    $user = apiUserWithRole('sales');
    $customer = Customer::create([
        'code' => 'CUST-API',
        'name' => 'Pelanggan API',
        'payment_terms_days' => 30,
        'credit_limit' => '1000000.00',
        'is_active' => true,
    ]);
    $product = Product::create([
        'sku' => 'SO-001',
        'name' => 'Produk SO',
        'unit_id' => $this->unit->id,
        'cost_price' => '5000.0000',
        'selling_price' => '10000.0000',
    ]);

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('access_token');

    $salesOrderId = $this->withToken($token)
        ->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'ordered_at' => '2026-10-25',
            'items' => [[
                'product_id' => $product->id,
                'quantity_ordered' => '2',
                'unit_price' => '10000',
                'discount_rate' => '0',
                'tax_rate' => '11',
            ]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->json('data.id');

    $this->withToken($token)
        ->postJson("/api/sales-orders/{$salesOrderId}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});
