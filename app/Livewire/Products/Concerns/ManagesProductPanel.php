<?php

namespace App\Livewire\Products\Concerns;

use App\Exceptions\Products\ProductException;
use App\Livewire\Forms\ProductForm;
use App\Models\Product;
use App\Services\ProductService;
use Livewire\WithFileUploads;

/**
 * Create / edit panel shared by the product list and the product details page.
 * The using component renders `livewire.products.partials.panel`. Components may define
 * `productSaved(Product $product)` to react after a save or status change.
 */
trait ManagesProductPanel
{
    use WithFileUploads;

    public ProductForm $form;

    public bool $showModal = false;

    public ?string $notice = null;

    public function create(): void
    {
        $this->authorize('create', Product::class);

        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(string $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->authorize('update', $product);

        $this->form->reset();
        $this->form->setProduct($product);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(ProductService $productService): void
    {
        $product = $this->form->product;

        $this->authorize($product === null ? 'create' : 'update', $product ?? Product::class);

        $payload = $this->form->payload();
        $image = $this->form->image;

        try {
            $saved = $product === null
                ? $productService->create($payload, auth()->user(), $image)
                : $productService->update($product, $payload, $image, $this->form->removeImage);
        } catch (ProductException $exception) {
            $this->addError('form.'.$exception->field, $exception->getMessage());

            return;
        }

        $this->notice = $product === null ? "{$saved->name} was added." : "{$saved->name} was updated.";
        $this->closeModal();
        $this->afterProductChange($saved);
    }

    public function deactivate(ProductService $productService): void
    {
        $product = $this->form->product;

        abort_if($product === null, 404);
        $this->authorize('changeStatus', $product);

        $productService->deactivate($product);

        $this->notice = "{$product->name} was deactivated.";
        $this->closeModal();
        $this->afterProductChange($product);
    }

    public function activate(ProductService $productService): void
    {
        $product = $this->form->product;

        abort_if($product === null, 404);
        $this->authorize('changeStatus', $product);

        $productService->activate($product);

        $this->notice = "{$product->name} was reactivated.";
        $this->closeModal();
        $this->afterProductChange($product);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->form->reset();
        $this->resetValidation();
    }

    private function afterProductChange(Product $product): void
    {
        if (method_exists($this, 'productSaved')) {
            $this->productSaved($product);
        }
    }
}
