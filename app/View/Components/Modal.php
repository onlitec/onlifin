<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Modal extends Component
{
    public $name;
    public $maxWidth;

    public function __construct($name, $maxWidth = '2xl')
    {
        $this->name = $name;
        $this->maxWidth = $maxWidth;
    }

    public function render()
    {
        return view('components.modal');
    }
} 