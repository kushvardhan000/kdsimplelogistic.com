<?php

namespace Tests\Feature;

use App\Http\Controllers\LogsheetController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LogsheetRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_logsheet_routes_have_callable_controller_methods()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();

            if (!str_starts_with($name, 'logsheets.')) {
                continue;
            }

            $action = $route->getAction('controller');
            $this->assertNotNull($action, "Route {$name} has no controller action");

            [$controllerClass, $method] = explode('@', $action);
            $this->assertTrue(
                class_exists($controllerClass),
                "Controller class {$controllerClass} for route {$name} does not exist"
            );

            $this->assertTrue(
                method_exists($controllerClass, $method),
                "Method {$method} does not exist on {$controllerClass} for route {$name}"
            );

            $controller = app()->make($controllerClass);
            $this->assertTrue(
                is_callable([$controller, $method]),
                "Method {$method} on {$controllerClass} is not callable for route {$name}"
            );
        }
    }

    public function test_logsheet_pages_return_200_and_no_download_links()
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        // /logsheets
        $response = $this->get(route('logsheets.index'));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));

        // /logsheets/records
        $response = $this->get(route('logsheets.records'));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));

        // /logsheets/imports/{id}
        $import = \App\Models\LogsheetImport::create([
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'original_filename' => 'test.xlsx',
            'file_path' => 'logsheet_imports/test.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 10,
            'consolidated_count' => 5,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => 1000.00,
            'total_booked_amount' => 1000.00,
            'total_diff' => 0.00,
            'total_gross_wt' => 500.000,
            'out_of_range_rows' => 0,
        ]);

        $response = $this->get(route('logsheets.imports.show', $import));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));
    }

    public function test_no_download_routes_exist()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();

            if (!str_starts_with($name, 'logsheets.')) {
                continue;
            }

            $this->assertFalse(
                str_contains($name, 'download'),
                "Download route {$name} should not exist"
            );
        }
    }
}