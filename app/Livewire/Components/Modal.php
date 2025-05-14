<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Modal extends Component
{
    public $isOpen = false;
    public $title = '';
    public $content = '';
    public $maxWidth = 'lg';
    public $closeable = true;

    protected $listeners = ['openModal'];

    public function openModal($title = '', $content = '', $maxWidth = 'lg')
    {
        $this->title = $title;
        $this->content = $content;
        $this->maxWidth = $maxWidth;
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->title = '';
        $this->content = '';
        $this->maxWidth = 'lg';
    }

    public function render()
    {
        return view('livewire.components.modal');
    }
}
