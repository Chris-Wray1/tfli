<?php
use Illuminate\Support\Facades\Http;

$page = (isset($_POST['page']) && is_numeric($_POST['page'])) ? $_POST['page'] : 1;
$page_size = (isset($_POST['page_size']) && is_numeric($_POST['page_size'])) ? $_POST['page_size'] : 25;
$product_type = (!empty($_POST['product_type']) && in_array($_POST['product_type'], ['all', 'beauty', 'food', 'petfood', 'product'])) ? $_POST['product_type'] : 'all';
$product_name = !empty($_POST['product_name']) ? $_POST['product_name'] : null;
$product_group = !empty($_POST['product_group']) ? $_POST['product_group'] : null;
$product_barcode = !empty($_POST['product_barcode']) ? $_POST['product_barcode'] : null;

$filters = [
    'page'          => $page,
    'page_size'     => $page_size,
    'product_type'  => $product_type,
];
if (!empty($product_group)) { $filters['product_group']     = $product_group; }
if (!empty($product_name)) { $filters['product_name']       = $product_name; }

$product_list = (Http::withOptions([ 'verify' => false, 'timeout' => 15.00, ])
                    ->get(env('APP_URL') . '/food/productList', $filters))
                    ->object();
$total_pages = 1;
if (!empty($product_list->count)) {
    $total_pages = ceil((int)$product_list->count / (int)$product_list->page_size) + 1;
}

$product_detail = (object) [];
if (!empty($product_barcode)) { 
    $filters['product_barcode'] = $product_barcode;
    $product_detail = (Http::withOptions([ 'verify' => false, 'timeout' => 15.00, ])
                        ->get(env('APP_URL') . '/food/' . $product_barcode, $filters))
                       ->object();
}

?>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('OpenFood') }}
        </h2>
    </x-slot>

    <div class="filter-container">

        <div class="filter-title">
            <span>{{ __("Filters") }}</span>
        </div>

        <div class="filter-reset card">
            <form action="/openfood" method="post">
                <button type="submit" class="btn btn-primary">Reset</button>
            </form>
        </div>

        <div class="filter-name card">
            <form action="/openfood" method="post">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Search by Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" aria-describedby="nameHelp">
                    <div id="nameHelp" class="form-text">Enter any part of the product name.</div>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="filter-barcode card">
            <form action="/openfood" method="post">
                <div class="mb-3">
                    <label for="product_barcode" class="form-label">Search for Barcode</label>
                    <input type="text" class="form-control" id="product_barcode" name="product_barcode" aria-describedby="barcodeHelp" value="{{ $product_barcode }}">
                    <div id="barcodeHelp" class="form-text">Enter the barcode.</div>
                    <input type="hidden" class="form-control" id="page" name="page" value="{{ $page }}">
                    <input type="hidden" class="form-control" id="product_type" name="product_type" value="{{ $product_type }}">
                    <input type="hidden" class="form-control" id="product_group" name="product_group" value="{{ $product_group }}">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="filter-type card">
            <form action="/openfood" method="post">
                <div class="mb-3">
                    <label for="product_type" class="form-label">Search for Product Type</label>
                    <select class="form-select" aria-label="Select Product Type" name="product_type" id="product_type" aria-describedby="typeHelp">
                        <?php foreach (['all', 'beauty', 'food', 'petfood', 'product'] as $type) { ?>
                            <option value="{{ $type }}" <?= $type == $product_type ? "selected" : ""; ?>>{{ $type }} </option>
                        <?php } ?>
                    </select>
                    <div id="typeHelp" class="form-text">Select the product type.</div>
                    <input type="hidden" class="form-control" id="product_barcode" name="product_barcode" value="{{ $product_barcode }}">
                    <input type="hidden" class="form-control" id="product_group" name="product_group" value="{{ $product_group }}">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="filter-group card">
            <form action="/openfood" method="post">
                <div class="mb-3">
                    <label for="product_group" class="form-label">Search by Product Group</label>
                    <input type="text" class="form-control" id="product_group" name="product_group" aria-describedby="groupHelp" value="{{ $product_group }}">
                    <div id="groupHelp" class="form-text">Enter any part of the product group.</div>
                    <?php if (!empty($product_group)) { ?>
                        <input type="hidden" class="form-control" id="page" name="page" value="{{ $page }}">
                    <?php } ?>
                    <input type="hidden" class="form-control" id="product_barcode" name="product_barcode" value="{{ $product_barcode }}">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="product-list card">
            <div class="card-header">
                Product List
            </div>

            <div class="table-responsive card-body">
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th scope="col">Barcode</th>
                            <th scope="col">Type</th>
                            <th scope="col">Group</th>
                            <th scope="col">Name</th>
                        </tr>
                    </thead>                        
                    <?php 
                    if (!empty($product_list->products)) {
                        foreach ($product_list->products as $product) { ?>
                            <tr>
                                <th scope="row"><?= $product->barcode; ?></th>
                                <td><?= $product->product_type; ?></td>
                                <td><?= !empty($product->food_groups[0]) ? $product->food_groups[0] : ''; ?></td>
                                <td><?= $product->product_name; ?></td>
                            </tr>
                        <?php } 
                    } ?>
                </table>
            </div>

            <div class="card-footer">
                <div x-data="{ page: {{ $page }} }">
                    <button class="page-control page-first" x-on:click="selectPage(1, '{{ json_encode($filters) }}')">First</button>
                    <button class="page-control page-prev" x-on:click="selectPage({{ $page - 1 }}, '{{ json_encode($filters) }}')">Previous</button>
                    <span x-text="page"></span>
                    <button class="page-control page-next" x-on:click="selectPage({{ $page + 1 }}, '{{ json_encode($filters) }}')">Next</button>
                    <button class="page-control page-last" x-on:click="selectPage({{ $total_pages }}, '{{ json_encode($filters) }}')">Last</button>
                </div>
            </div>

        </div>

        <div class="product card">
            <div class="card-header">
                Product Detail
            </div>

            <div class="table-responsive card-body">
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th scope="col">Section</th>
                            <th scope="col">Detail</th>
                        </tr>
                    </thead>                        
                    <?php 
                    if (!empty($product_detail->product)) {
                        foreach ($product_detail->product as $section => $detail) { 
                            $detail = !is_array($detail) ? [$detail] : $detail;  
                            ?>
                            <tr>
                                <th scope="row"><?= $section; ?></th>
                                <td><?= implode(', ', $detail); ?></td>
                            </tr>
                        <?php } 
                    } ?>
                </table>
            </div>

            <div class="card-footer">
            </div>

        </div>

    </div>


    <div class="py-12">
        <pre>
            <?php print_r($filters); ?>
        </pre>
    </div>


</x-app-layout>
