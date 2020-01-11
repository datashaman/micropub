<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    /**
     * @param EntryRequest
     */
    public function store(EntryRequest $request)
    {
        return $request->validated();
    }
}
