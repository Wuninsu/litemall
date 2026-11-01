<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ButtonTest extends TestCase
{
    public function test_it_renders_a_plain_button_without_a_loader(): void
    {
        $html = Blade::render('<x-button>Plain</x-button>');

        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('Plain', $html);
        $this->assertStringNotContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('wire:loading', $html);
    }

    public function test_wire_click_buttons_get_a_loader_targeting_that_action(): void
    {
        $html = Blade::render('<x-button icon="plus" wire:click="create">New user</x-button>');

        $this->assertStringContainsString('wire:click="create"', $html);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $html);
        $this->assertStringContainsString('wire:target="create"', $html);
        $this->assertStringContainsString('animate-spin', $html);
        $this->assertStringContainsString('wire:loading.remove wire:target="create" class="contents"', $html);
    }

    public function test_wire_click_with_arguments_is_escaped_into_the_target(): void
    {
        $html = Blade::render('<x-button size="icon" icon="edit" wire:click="edit(\'abc-123\')" aria-label="Edit" />');

        $this->assertStringContainsString('wire:target="edit(&#039;abc-123&#039;)"', $html);
        $this->assertStringContainsString('size-9', $html);
        $this->assertStringNotContainsString('size-5', $html);
    }

    public function test_submit_buttons_swap_their_label_for_loading_text(): void
    {
        $html = Blade::render('<x-button type="submit" target="save" loading-text="Saving…" block>Save</x-button>');

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringContainsString('<span wire:loading.remove wire:target="save">Save</span>', $html);
        $this->assertStringContainsString('<span wire:loading wire:target="save">Saving…</span>', $html);
    }

    public function test_submit_buttons_without_a_target_spin_for_any_request(): void
    {
        $html = Blade::render('<x-button type="submit">Go</x-button>');

        $this->assertStringContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('wire:target', $html);
    }

    public function test_the_loader_can_be_switched_off(): void
    {
        $html = Blade::render('<x-button wire:click="closeModal" :loading="false">Cancel</x-button>');

        $this->assertStringNotContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('wire:loading', $html);
    }

    public function test_href_renders_a_navigating_link_with_variant_classes(): void
    {
        $html = Blade::render('<x-button href="/users" variant="danger" icon-right="x">Go</x-button>');

        $this->assertStringContainsString('<a href="/users"', $html);
        $this->assertStringContainsString('wire:navigate', $html);
        $this->assertStringContainsString('text-red-700', $html);
        $this->assertStringNotContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('<button', $html);
    }
}
