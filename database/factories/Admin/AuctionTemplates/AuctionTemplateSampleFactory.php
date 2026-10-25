<?php

declare(strict_types=1);

namespace Database\Factories\Admin\AuctionTemplates;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AuctionTemplates\AuctionTemplateSample;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuctionTemplateSample> */
class AuctionTemplateSampleFactory extends Factory
{
    protected $model = AuctionTemplateSample::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(AuctionTemplateType::cases()),
            'description' => fake()->optional()->sentence(),
            'preview_image_path' => null,
            'schema' => ['schema_version' => 2, 'pages' => []],
            'is_active' => true,
            'created_by' => null,
        ];
    }
}
