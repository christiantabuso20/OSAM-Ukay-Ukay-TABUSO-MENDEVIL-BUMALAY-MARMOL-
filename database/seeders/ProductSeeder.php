<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'T-Shirts' => [
                ['name' => 'Vintage Graphic Tee', 'type' => 'Streetwear', 'size' => 'M', 'condition' => 'Good', 'price' => 149, 'stock' => 12, 'image' => 'products/vintage-graphic-tee.png'],
                ['name' => 'Soft Cotton Basic Tee', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Excellent', 'price' => 129, 'stock' => 15, 'image' => 'products/soft-cotton-basic-tee.png'],
                ['name' => 'Striped Everyday Tee', 'type' => 'Casual Wear', 'size' => 'S', 'condition' => 'Good', 'price' => 139, 'stock' => 10, 'image' => 'products/striped-everyday-tee.png'],
            ],
            'Polo Shirts' => [
                ['name' => 'Classic Pique Polo', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Excellent', 'price' => 249, 'stock' => 9, 'image' => 'products/classic-pique-polo.png'],
                ['name' => 'Ribbed Collar Polo', 'type' => 'Formal Wear', 'size' => 'M', 'condition' => 'Good', 'price' => 229, 'stock' => 8, 'image' => 'products/ribbed-collar-polo.png'],
                ['name' => 'Colorblock Weekend Polo', 'type' => 'Streetwear', 'size' => 'XL', 'condition' => 'Good', 'price' => 199, 'stock' => 7, 'image' => 'products/colorblock-weekend-polo.png'],
            ],
            'Pants' => [
                ['name' => 'Straight-Leg Jeans', 'type' => 'Casual Wear', 'size' => '32', 'condition' => 'Good', 'price' => 399, 'stock' => 6, 'image' => 'products/straight-leg-jeans.png'],
                ['name' => 'Wide-Leg Trousers', 'type' => 'Formal Wear', 'size' => '34', 'condition' => 'Excellent', 'price' => 349, 'stock' => 7, 'image' => 'products/wide-leg-trousers.png'],
                ['name' => 'Olive Cargo Pants', 'type' => 'Streetwear', 'size' => '30', 'condition' => 'Good', 'price' => 379, 'stock' => 5, 'image' => 'products/olive-cargo-pants.png'],
            ],
            'Shorts' => [
                ['name' => 'Linen Shorts', 'type' => 'Casual Wear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 179, 'stock' => 11, 'image' => 'products/linen-shorts.png'],
                ['name' => 'Classic Denim Shorts', 'type' => 'Streetwear', 'size' => '30', 'condition' => 'Good', 'price' => 199, 'stock' => 8, 'image' => 'products/classic-denim-shorts.png'],
                ['name' => 'Black Tailored Shorts', 'type' => 'Formal Wear', 'size' => 'L', 'condition' => 'Good', 'price' => 199, 'stock' => 10, 'image' => 'products/black-tailored-shorts.png'],
            ],
            'Dresses' => [
                ['name' => 'Floral Midi Dress', 'type' => 'Casual Wear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 449, 'stock' => 5, 'image' => 'products/floral-midi-dress.png'],
                ['name' => 'Burgundy Satin Slip Dress', 'type' => 'Formal Wear', 'size' => 'S', 'condition' => 'Good', 'price' => 499, 'stock' => 4, 'image' => 'products/black-slip-dress.png'],
                ['name' => 'Blue Floral Sundress', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Excellent', 'price' => 399, 'stock' => 6, 'image' => 'products/blue-sundress.png'],
            ],
            'Jackets' => [
                ['name' => 'Classic Denim Jacket', 'type' => 'Streetwear', 'size' => 'L', 'condition' => 'Good', 'price' => 599, 'stock' => 4, 'image' => 'products/classic-denim-jacket.png'],
                ['name' => 'Lightweight Windbreaker', 'type' => 'Sportswear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 549, 'stock' => 5, 'image' => 'products/lightweight-windbreaker.png'],
                ['name' => 'Corduroy Overshirt Jacket', 'type' => 'Casual Wear', 'size' => 'XL', 'condition' => 'Good', 'price' => 649, 'stock' => 3, 'image' => 'products/corduroy-overshirt-jacket.png'],
            ],
            'Sweaters' => [
                ['name' => 'Cozy Cable Knit Sweater', 'type' => 'Casual Wear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 399, 'stock' => 6, 'image' => 'products/cozy-cable-knit-sweater.png'],
                ['name' => 'Classic Crewneck Pullover', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Good', 'price' => 349, 'stock' => 7, 'image' => 'products/classic-crewneck-pullover.png'],
                ['name' => 'Soft V-Neck Knit', 'type' => 'Formal Wear', 'size' => 'S', 'condition' => 'Good', 'price' => 329, 'stock' => 5, 'image' => 'products/soft-v-neck-knit.png'],
            ],
            'Skirts' => [
                ['name' => 'Pleated Midi Skirt', 'type' => 'Formal Wear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 299, 'stock' => 6, 'image' => 'products/pleated-midi-skirt.png'],
                ['name' => 'A-Line Denim Skirt', 'type' => 'Casual Wear', 'size' => 'S', 'condition' => 'Good', 'price' => 249, 'stock' => 7, 'image' => 'products/a-line-denim-skirt.png'],
                ['name' => 'Flowy Maxi Skirt', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Good', 'price' => 279, 'stock' => 5, 'image' => 'products/flowy-maxi-skirt.png'],
            ],
            'Blouses' => [
                ['name' => 'Satin Button-Up Blouse', 'type' => 'Formal Wear', 'size' => 'M', 'condition' => 'Excellent', 'price' => 299, 'stock' => 8, 'image' => 'products/satin-button-up-blouse.jpg'],
                ['name' => 'Embroidered Peasant Blouse', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Good', 'price' => 249, 'stock' => 6, 'image' => 'products/embroidered-peasant-blouse.jpg'],
                ['name' => 'Polka Dot Wrap Blouse', 'type' => 'Casual Wear', 'size' => 'S', 'condition' => 'Good', 'price' => 229, 'stock' => 7, 'image' => 'products/polka-dot-wrap-blouse.jpg'],
            ],
            'Others' => [
                ['name' => 'Classic Denim Vest', 'type' => 'Streetwear', 'size' => 'M', 'condition' => 'Good', 'price' => 279, 'stock' => 5, 'image' => 'products/classic-denim-vest.jpg'],
                ['name' => 'Unisex Canvas Overalls', 'type' => 'Casual Wear', 'size' => 'L', 'condition' => 'Excellent', 'price' => 449, 'stock' => 4, 'image' => 'products/unisex-canvas-overalls.jpg'],
                ['name' => 'Soft Fleece Hoodie', 'type' => 'Sportswear', 'size' => 'XL', 'condition' => 'Good', 'price' => 399, 'stock' => 6, 'image' => 'products/soft-fleece-hoodie.jpg'],
            ],
        ];

        $skuNumber = 1;
        foreach ($products as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($items as $item) {
                $sku = 'OSAM-DEMO-'.str_pad((string) $skuNumber, 3, '0', STR_PAD_LEFT);
                $stock = $item['stock'];

                Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $item['name'],
                        'category_id' => $category->id,
                        'clothing_type' => $item['type'],
                        'size' => $item['size'],
                        'condition' => $item['condition'],
                        'description' => 'Curated secondhand find from the OSAM Ukay-Ukay starter collection.',
                        'unit_price' => $item['price'],
                        'initial_stock' => $stock,
                        'available_stock' => $stock,
                        'minimum_stock' => 3,
                        'image_path' => $item['image'] ?? null,
                    ]
                );

                $skuNumber++;
            }
        }
    }
}
