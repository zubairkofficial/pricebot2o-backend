<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::all();

        return view('category.index', compact('categories'));
    }

    public function create(Request $request): Response
    {
        return view('category.create');
    }

    public function store(CategoryStoreRequest $request): Response
    {
        $category = Category::create($request->validated());

        return redirect()->route('category.index');
    }
}
