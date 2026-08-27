<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_with_the_seeded_credentials_pattern(): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@order-service.test',
            'password' => Hash::make('admin'),
        ]);

        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin',
        ])->assertRedirect(route('admin.orders.index'));

        $this->assertAuthenticated();
    }

    public function test_orders_page_requires_authentication(): void
    {
        $this->get('/admin/orders')->assertRedirect(route('login'));
    }

    public function test_admin_can_search_orders_by_restaurant_and_view_order_details(): void
    {
        $admin = User::factory()->create();
        $matchingRestaurant = Restaurant::factory()->create(['name' => 'Noodle House']);
        $otherRestaurant = Restaurant::factory()->create(['name' => 'Pizza Place']);
        $client = Client::factory()->create(['name' => 'Douglas Miguel']);
        $matchingOrder = Order::factory()->create([
            'client_id' => $client->id,
            'restaurant_id' => $matchingRestaurant->id,
        ]);
        Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $this->actingAs($admin)
            ->get('/admin/orders?restaurant=Noodle')
            ->assertOk()
            ->assertSee('Noodle House')
            ->assertSee('Douglas Miguel')
            ->assertSee('#'.$matchingOrder->id)
            ->assertDontSee('Pizza Place')
            ->assertSee('Details');
    }

    public function test_orders_are_paginated_fifteen_per_page(): void
    {
        $admin = User::factory()->create();
        $restaurant = Restaurant::factory()->create();
        Order::factory()->count(16)->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($admin)
            ->get('/admin/orders?page=2')
            ->assertOk()
            ->assertSee('Showing 16–16 of 16')
            ->assertSee('Previous');
    }

    public function test_most_recent_orders_are_listed_first(): void
    {
        $admin = User::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $olderOrder = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'created_at' => now()->subDays(10),
        ]);
        $recentOrder = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSeeInOrder(['#'.$recentOrder->id, '#'.$olderOrder->id]);
    }
}
