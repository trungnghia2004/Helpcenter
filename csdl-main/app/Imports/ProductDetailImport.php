<?php

namespace App\Imports;

use App\Models\ProductDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductDetailImport implements ToCollection
{
    protected $productID;

    public function __construct($productID)
    {
        $this->productID = $productID;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if ($row[0] == 'sizeId') continue; // Bỏ qua dòng tiêu đề

            $sizeId = $row[0];
            $colorId = $row[1];
            $quantity = $row[2];

            $existing = ProductDetail::where('prdID', $this->productID)
                ->where('sizeId', $sizeId)
                ->where('colorId', $colorId)
                ->where('isDeleted', false)
                ->first();

            if ($existing) {
                $existing->productQuantity += $quantity;
                $existing->save();
            } else {
                ProductDetail::create([
                    'prdID' => $this->productID,
                    'sizeId' => $sizeId,
                    'colorId' => $colorId,
                    'productQuantity' => $quantity,
                    'isDeleted' => false,
                ]);
            }
        }
    }
}
