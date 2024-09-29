<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attribute;
use App\Models\Image;
use App\Models\Variation;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductImage;
use App\Models\Variant;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $filePath = storage_path('app/sample-data/minebotanicals_products.xlsx');
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $productsAlreadyCreated = [];
        foreach ($rows as $key => $row) {
            // Skip the first row if it's the header
            if ($key == 1) {
                continue;
            }

            $productTitle = $row['A'];
            if (in_array($productTitle, $productsAlreadyCreated)) {
                info("Product already created: {$productTitle}");
                continue;
            }
            info("Creating product: {$productTitle}");
            // Parse categories, tags, images, and variants from JSON-like strings
            // categories: ["category1", "category2"]
            $categoriesString = str_replace("'", '"', trim($row['E'], "[]"));
            $categories = json_decode("[$categoriesString]", true);
            
            // tags: ["tag1", "tag2"]
            $tagsString = str_replace("'", '"', trim($row['F'], "[]"));
            $tags = json_decode("[$tagsString]", true);

            // images: ["image1.jpg", "image2.jpg"]
            $imagesString = str_replace("'", '"', trim($row['K'], "[]"));
            $images = json_decode("[$imagesString]", true);
            
            // variants: [{'title': '8 oz', 'sku': 'WSB-AE-8-01', 'public_title': '8 oz', 'options': ['8 oz'], 'price': '11.99', 'weight': 227}, {'title': '12 oz', 'sku': 'WSB-AE-12-01', 'public_title': '12 oz', 'options': ['12 oz'], 'price': '14.99', 'weight': 340}]
            $variantsString = str_replace("'", '"', trim($row['J'], "[]"));
            $variants = json_decode("[$variantsString]", true);
        
            // Create the product
            $product = Product::create([
                'name' => $productTitle,
                'short_description' => $row['B'],
                'vendor' => $row['C'],
                'price' => $row['G'],
                'allow_out_of_stock_orders' => true,
                'slug' => Str::slug($productTitle),
                'description' => $row['M'],
                'status' => 'published',
                'published_at' => now(),
                'meta_title' => $productTitle,
                // meta_description remove all special characters from  $row['B'] allow only alphanumeric characters & comma, space, period, hyphen and underscore
                'meta_description' => preg_replace('/[^A-Za-z0-9 ,\.\-_]/', '', $row['B']),
                // generate meta_keywords from $productTitle,  $categories, $tags must be unique and separated by comma
                'meta_keywords' => implode(',', array_unique(array_merge([$productTitle], $categories, $tags))),
                'featured_image' => $this->downloadImage($row['L'], $productTitle, 'featured')
            ]);

            // Attach categories
            if(empty($categories)) {
                foreach ($categories as $category) {
                    $categoryModel = Category::firstOrCreate(['name' => $category, 'slug' => Str::slug($category)]);
                    $product->categories()->attach($categoryModel);
                }
            }

            // Attach tags
            if(!empty($tags)){
                foreach ($tags as $tag) {
                    $tagModel = Tag::firstOrCreate(['name' => $tag, 'slug' => Str::slug($tag)]);
                    $product->tags()->attach($tagModel);
                }
            }

            // Download product images and save to `product_images`
            if(!empty($images)) {
                info("Downloading multiple images for {$productTitle}");
                foreach ($images as $image) {
                    $imagePath = $this->downloadImage($image, $productTitle, 'product');
                    info("Image downloaded: {$imagePath}");
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'caption' => $productTitle,  // You can add a caption if available
                    ]);
                }
            }

            // Handle and save variants
            if(!empty($variants)) {
                foreach ($variants as $variant) {
                    Variant::create([
                        'product_id' => $product->id,
                        'size' => $variant['options'][0] ?? null,  // Assuming size is in the first option
                        'color' => $variant['options'][1] ?? null,  // Assuming color is in the second option if available
                        'price' => $variant['price'],
                        'sku' => $variant['sku'] ?? null,
                        'stock' => 0, // Add stock logic if available
                    ]);
                }
            }

            $productsAlreadyCreated[] = $productTitle;
        }
    }

    /**
     * Download an image from a URL and save it to the storage
     */
    // Function to download image and store it in the local storage
    private function downloadImage($url, $productName, $type)
    {
        if (!$url) return null;

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                // Get the file extension from the response headers
                $extension = $this->getExtensionFromResponse($response);
                
                if ($extension) {
                    $imageName = Str::slug($productName) . '-' . $type . '-' . time() . '.' . $extension;
                    $imagePath = 'products/' . $imageName;
                    
                    // Store the image in the public disk
                    Storage::put('public/' . $imagePath, $response->body());
                    
                    return 'storage/' . $imagePath;  // Return the stored image path for database reference
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to download image from {$url}. Error: " . $e->getMessage());
            return null;
        }
    }

    // Function to extract the image extension from the response headers
    private function getExtensionFromResponse($response)
    {
        // Extract the Content-Type header
        $mimeType = $response->header('Content-Type');

        // Map MIME type to extension
        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            // Add more mappings as needed
        ];

        return $extensionMap[$mimeType] ?? null;
    }

}
