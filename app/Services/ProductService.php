<?php

namespace App\Services;

use App\Exceptions\Products\ProductException;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Maintains the product list, current selling prices, stock quantities and product photos.
 * Changing a price affects future sales only; sales will store their own price snapshot (ADR-0006).
 * Products are deactivated, never deleted.
 */
class ProductService
{
    /**
     * @param  array{name: string, sku?: string|null, category?: string|null, price_pesewas: int, stock_quantity?: int, description?: string|null}  $data
     *
     * @throws ProductException
     */
    public function create(array $data, User $actor, ?UploadedFile $image = null): Product
    {
        return $this->persist(new Product([
            'is_active' => true,
            'created_by' => $actor->getKey(),
        ]), $data, $image, removeImage: false);
    }

    /**
     * Updates details, price and stock. A new `$image` replaces the current photo; `$removeImage`
     * deletes it. Old files are removed from storage only after the database update succeeds.
     *
     * @param  array{name: string, sku?: string|null, category?: string|null, price_pesewas: int, stock_quantity?: int, description?: string|null}  $data
     *
     * @throws ProductException
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        return $this->persist($product, $data, $image, $removeImage);
    }

    public function deactivate(Product $product): void
    {
        $product->forceFill(['is_active' => false])->save();
    }

    public function activate(Product $product): void
    {
        $product->forceFill(['is_active' => true])->save();
    }

    /**
     * Distinct categories in use, for filters and suggestions.
     *
     * @return list<string>
     */
    public function categories(): array
    {
        return Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /**
     * @param  array{name: string, sku?: string|null, category?: string|null, price_pesewas: int, stock_quantity?: int, description?: string|null}  $data
     *
     * @throws ProductException
     */
    private function persist(Product $product, array $data, ?UploadedFile $image, bool $removeImage): Product
    {
        if ($data['price_pesewas'] <= 0) {
            throw ProductException::invalidPrice();
        }

        $stock = $data['stock_quantity'] ?? $product->stock_quantity ?? 0;

        if ($stock < 0) {
            throw ProductException::invalidStock();
        }

        $sku = Str::upper(trim((string) ($data['sku'] ?? '')));
        $category = Str::squish((string) ($data['category'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        $product->fill([
            'name' => Str::squish($data['name']),
            'sku' => $sku === '' ? null : $sku,
            'category' => $category === '' ? null : Str::ucfirst($category),
            'price_pesewas' => $data['price_pesewas'],
            'stock_quantity' => $stock,
            'description' => $description === '' ? null : $description,
        ]);

        $previousImage = $product->getOriginal('image_path');
        $newImage = $image?->store(Product::IMAGE_DIRECTORY, Product::IMAGE_DISK);

        if ($image !== null && $newImage === false) {
            throw ProductException::imageNotSaved();
        }

        if ($newImage) {
            $product->image_path = $newImage;
        } elseif ($removeImage) {
            $product->image_path = null;
        }

        try {
            $product->save();
        } catch (UniqueConstraintViolationException) {
            $this->deleteImage($newImage ?: null);

            throw ProductException::skuTaken();
        } catch (Throwable $exception) {
            $this->deleteImage($newImage ?: null);

            throw $exception;
        }

        if ($previousImage && $previousImage !== $product->image_path) {
            $this->deleteImage($previousImage);
        }

        return $product;
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk(Product::IMAGE_DISK)->delete($path);
        }
    }
}
