<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\UserProduct;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserProductController extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp(); // TODO: Change the autogenerated stub
	}

	/**
	 * @param array $data
	 * @param array $resultStructure
	 * @param int $statusCode
	 * @return void
	 * @covers       \App\Http\Controllers\UserProductController::create
	 * @dataProvider createDataProvider
	 */
	public function testCreate(array $data, array $resultStructure, int $statusCode): void
	{
		if ($statusCode == Response::HTTP_CREATED) {
			$data['user']       = $this->user;
			$data['product_id'] = Product::factory()->create()->id;
		}

		$this->withoutMiddleware();
		$response = $this->post("/api/user-products/create", $data);
		$response->assertStatus($statusCode);
		$response->assertJsonStructure($resultStructure);
	}

	public static function createDataProvider(): array
	{
		return [
			[
				'data'             => [],
				'result_structure' => [
					'success',
					'data' => [
						'id',
						'user_id',
						'product_id',
						'created_at',
						'updated_at',
					],
					'message',
				],
				'status_code'      => Response::HTTP_CREATED
			],
			[
				'data'             => [],
				'result_structure' => [
					'message',
					'errors',
				],
				'status_code'      => Response::HTTP_UNPROCESSABLE_ENTITY
			]
		];
	}

	/**
	 * @param array $resultStructure
	 * @param int $statusCode
	 * @return void
	 * @covers       \App\Http\Controllers\UserProductController::delete
	 * @dataProvider deleteDataProvider
	 */
	public function testDelete(array $resultStructure, int $statusCode): void
	{
		if ($statusCode == Response::HTTP_OK) {
			$productId   = Product::factory()->create()->id;
			$userProduct = UserProduct::factory()->create([
				'user_id'    => $this->user->id,
				'product_id' => $productId
			]);

			$id = $userProduct->id;
		} else {
			$id = 1000;
		}

		$this->withoutMiddleware();
		$response = $this->delete("/api/user-products/delete/$id");
		$response->assertStatus($statusCode);
		$response->assertJsonStructure($resultStructure);
	}

	public static function deleteDataProvider(): array
	{
		return [
			[
				'result_structure' => [
					'success',
					'data',
					'message',
				],
				'status_code'      => Response::HTTP_OK
			],
			[
				'result_structure' => [
					'success',
					'data',
					'message',
				],
				'status_code'      => Response::HTTP_NOT_FOUND
			]
		];
	}

	/**
	 * @return void
	 * @covers \App\Http\Controllers\UserProductController::getList
	 */
	public function getList(): void
	{
		$params['user'] = $this->user;

		$this->withoutMiddleware();
		$response = $this->json('GET', '/api/user-products/list', $params);
		$response->assertStatus(Response::HTTP_OK);
		$response->assertJsonStructure([
			'success',
			'data' => [
				'*' => [
					'id',
					'user_id',
					'product_id',
					'created_at',
					'updated_at',
					'product' => [
						'id',
						'name',
						'price',
						'created_at',
						'updated_at',
					],
				]
			],
			'message',
		]);
	}
}