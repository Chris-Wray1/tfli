<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class OpenFoodFacts extends Controller
{
	private $api = "https://world.openfoodfacts.org/api/v2/";
	private $barcode;
	private $product_type = "all";
	private $user_id = 'tfli-chris-wray';
	private $password = 'Tadjba!4';
	private $user_agent = 'Laravel Open Food Facts - https://github.com/openfoodfacts/openfoodfacts-laravel';
	Private $comms_error = [
			'error_code'	=> 100,
			'error_name'	=> 'OpenFood_API_Error',
			'description'   => 'There was a communication error with the OpenFood API',
		];
    private $product_name;
    private $product_group;

    public $page = 1;
    public $page_size = 25;
    public $sort_by = 'product_name';

	public function __construct(Request $request) 
	{
		$validated = $request->validate([
			'page'          => ["numeric", "integer"],
			'page_size'     => ["numeric", "integer"],
			'product_type'  => ['string'],
            'product_name'  => ['string'],
            'product_group' => ['string'],
		]);
		if(!empty($validated['page'])) { $this->page((int)$validated['page']); }
		if(!empty($validated['page_size'])) { $this->pageSize((int)$validated['page_size']); }
		if(!empty($validated['product_type'])) { $this->productType((string)$validated['product_type']); }
        if(!empty($validated['product_name'])) { $this->productSearchName((string)$validated['product_name']); }
        if(!empty($validated['product_group'])) { $this->productGroup((string)$validated['product_group']); }
	}

	public function page(int $page) 
	{
		$this->page = $page;
	}

	public function pageSize(int $page_size) 
	{
		$this->page_size = $page_size;
	}

	public function barcode(int $barcode) 
	{
		$this->barcode = $barcode;
	}

	public function productType(string $product_type) 
	{
		$this->product_type = 'all';
		if(in_array($product_type, ['all', 'beauty', 'food', 'petfood', 'product'])) {
			$this->product_type = $product_type;
		}
	}

    public function productGroup(string $product_group)
    {
        $this->product_group = $product_group;
    }

    public function productSearchName(string $product_name)
    {
        $this->product_name = $product_name;
    }

	public function productList() 
	{
        $filters = [];
        if (!empty($this->page)) { $filters['page'] = $this->page; }
        if (!empty($this->page_size)) { $filters['page_size'] = $this->page_size; }

        // Commented out as led to timeouts in testing
#        if (!empty($this->sort_by)) { $filters['sort_by'] = $this->sort_by; }

		$response = Http::withHeaders([
				'User-Agent'	=> $this->user_agent,
			])
            ->timeout(300)
            ->get($this->api . 'search', $filters);

		if ($response->successful()) {
			$response = $response->object();

			$response->products = $this->filterProducts($response->products);

            if ($this->product_type != 'all') {
                $response->products = $this->limitProducts($response->products, 'product_type');
            }
            $response->products = $this->limitProducts($response->products, 'product_name');
            $response->products = $this->limitProducts($response->products, 'product_group');

			return json_encode($response);
		}

		return json_encode((object)$this->comms_error);
	}

	public function product() 
	{
		$response = Http::withHeaders([
				'User-Agent'	=> $this->user_agent,
			])->get($this->api . 'product/' . $this->barcode);

		if ($response->successful()) {
			$response = $response->object();

            $product = (object) [
                'barcode'           => !empty($response->code) ? $response->code : null,
                'product'           => !empty($response->product) ? (object) $this->filterFields($response->product) : null,
                'status'            => !empty($response->status) ? $response->status : 0,
                'status_verbose'    => !empty($response->status_verbose) ? $response->status_verbose : 'product not found',
            ];

			return json_encode($product);
            return json_encode($response);
		}

		return json_encode((object)$this->comms_error);
	}

    private function limitProducts(array $productList, ?string $limit = null)
    {
        $limitedList = [];
        if(!empty($this->{$limit})) {
            foreach ($productList as $product) {
                if (str_contains(strtolower($product[$limit]), strtolower($this->{$limit}))) {
                    $limitedList[] = $product;
                }
            }
            return $limitedList;
        }
        return $productList;
    }

    private function filterProducts(array $productList) 
    {
        $products = [];
        foreach ($productList as $product) {
            $products[] = $this->filterFields($product);
        }

        return $products;
    }

    private function filterFields(object $product)
    {
        return [
            '_id'                   => $this->productId($product),
            'barcode'               => !empty($product->code) ? $product->code : null,
            'brands'                => $this->brands($product),
            'categories'            => $this->categories($product),
            'countries'             => $this->countries($product),
            'ecoscore_grade'        => !empty($product->ecoscore_grade) ? $product->ecoscore_grade : null,
            'ecoscore'              => $this->ecoscore($product),
            'food_groups'           => $this->foodGroups($product),
            'image'                 => $this->image($product),
            'image_thumb'           => $this->thumbnail($product),
            'ingredients'           => $this->ingredients($product),
            'ingredients_analysis'  => $this->ingredientsAnalysis($product),
            'ingredients_traces'    => $this->traces($product),
            'labels'                => $this->labels($product),
            'nova_groups'           => $this->novaGroups($product),
            'nutriscore'            => $this->nutriScore($product),
            'nutrient_levels'       => $this->nutrientLevels($product),
            'origin'                => $this->origin($product),
            'packaging'             => $this->packaging($product),
            'recycling'             => $this->recycling($product),
            'product_name'          => $this->productName($product),
            'product_type'          => !empty($product->product_type) ? $product->product_type : null,
            'stores'                => $this->stores($product),
        ];
    }

    private function productId(object $product)
    {
        foreach (['_id', 'id', 'code'] as $key) {
            if(!empty($product->{$key})) {
                return $product->{$key};
            }
        }
        return null;
    }

	private function productName(object $product) 
	{
		foreach (['product_name_en', 'generic_name_en', 'product_name', 'generic_name'] as $key) {
			if (!empty($product->{$key})) {
				return $product->{$key};
			}
		}
		return null;
	}

	private function filterTags($tags) 
	{
        $tags = !is_array($tags) ? [$tags] : $tags;
		$filtered = [];
		foreach ($tags as $tag) {
			if(!is_numeric(stripos($tag, ':'))) {
				$filtered[] = ucwords(trim(str_ireplace('-', ' ', $tag)));
                continue;
			}
            foreach (explode(':', $tag) as $option) {
                if(strlen($option) > 2) {
                    $filtered[] = ucwords(trim(str_ireplace('-', ' ', $option)));
                }
            }
		}
		return $filtered;
	}

	private function brands($product) 
	{
        if(!empty($product->brands)) {
            return $this->filterTags($product->brands);
        }
		return [];
	}

    private function categories($product)
    {
        if(!empty($product->categories_tags_en)) {
            return $this->filterTags($product->categories_tags_en);
        }
        if(!empty($product->categories_tags)) {
            return $this->filterTags($product->categories_tags);
        }
        return [];
    }

    private function countries($product)
    {
        if(!empty($product->countries_tags)) {
            return $this->filterTags($product->countries_tags);
        }
        return [];
    }

    private function ecoScore($product)
    {
        if(!empty($product->ecoscore_tags_en)) {
            return $this->filterTags($product->ecoscore_tags_en);
        }
        if(!empty($product->ecoscore_tags)) {
            return $this->filterTags($product->ecoscore_tags);
        }
        return [];
    }

    private function foodGroups($product)
    {
        if(!empty($product->food_groups_tags_en)) {
            return $this->filterTags($product->food_groups_tags_en);
        }
        if(!empty($product->food_groups_tags)) {
            return $this->filterTags($product->food_groups_tags);
        }
        return [];
    }

    private function thumbnail($product)
    {
        if(!empty($product->image_front_thumb_url)) {
            return $product->image_front_thumb_url;
        }
        if(!empty($product->image_front_small_url)) {
            return $product->image_front_small_url;
        }
        return null;
    }

    private function image($product)
    {
        if(!empty($product->image_front_url)) {
            return $product->image_front_url;
        }
        if(!empty($product->image_front_small_url)) {
            return $product->image_front_small_url;
        }
        if(!empty($product->image_url)) {
            return $product->image_url;
        }
        return null;
    }

    private function ingredientsAnalysis($product)
    {
        if(!empty($product->ingredients_analysis_tags_en)) {
            return $this->filterTags($product->ingredients_analysis_tags_en);
        }
        if(!empty($product->ingredients_analysis_tags)) {
            return $this->filterTags($product->ingredients_analysis_tags);
        }
        return [];
    }

    private function ingredients($product)
    {
        if(!empty($product->ingredients_tags_en)) {
            return $this->filterTags($product->ingredients_tags_en);
        }
        if(!empty($product->ingredients_tags)) {
            return $this->filterTags($product->ingredients_tags);
        }
        if(!empty($product->ingredients_original_tags)) {
            return $this->filterTags($product->ingredients_original_tags);
        }
        return [];
    }

    private function labels($product)
    {
        if(!empty($product->labels_tags_en)) {
            return $this->filterTags($product->labels_tags_en);
        }
        if(!empty($product->labels_hierarchy_en)) {
            return $this->filterTags($product->labels_hierarchy_en);
        }
        if(!empty($product->labels_tags)) {
            return $this->filterTags($product->labels_tags);
        }
        if(!empty($product->labels_hierarchy)) {
            return $this->filterTags($product->labels_hierarchy);
        }
        return [];
    }

    private function novaGroups($product)
    {
        if(!empty($product->nova_groups_tags_en)) {
            return $this->filterTags($product->nova_groups_tags_en);
        }
        if(!empty($product->nova_groups_tags)) {
            return $this->filterTags($product->nova_groups_tags);
        }
        return [];
    }

    private function nutriScore($product)
    {
        $contains = [];
        $summary = [];
        $grade = [];
        $score = [];

        if (!empty($product->nutriscore)) {
            $nutriScore = json_decode(json_encode($product->nutriscore), true);

            foreach (['2021', '2022', '2023', '2024'] as $version) {
                if (!empty($nutriScore[$version])) {
                    foreach ($nutriScore[$version]['data'] as $key => $value) {
                        if(str_starts_with( $key, 'is_' )) {
                            $contains[$key] = $value;
                        }
                    }
                    $grade[] = !empty($nutriScore[$version]['grade']) ? $version . '_grade_' . $nutriScore[$version]['grade'] : null;
                    $score[] = (isset($nutriScore[$version]['score']) && is_numeric($nutriScore[$version]['score'])) ? $version . '_score_' . $nutriScore[$version]['score'] : null;
                }
            }

            foreach ($contains as $key => $value) {
                if (isset($value) && is_numeric($value) && $value > 0) {
                    $summary[] = $key;
                }
            }
            if (!empty($grade)) {
                $summary[] = implode(', ', $grade);
            }
            if (!empty($score)) {
                $summary[] = implode(', ', $score);
            }
        }

        return $summary;
    }

    private function nutrientLevels($product)
    {
        if (!empty($product->nutrient_levels_en)) {
            return $this->obj2arr($product->nutrient_levels_en);
        }
        if (!empty($product->nutrient_levels)) {
            return $this->obj2arr($product->nutrient_levels);
        }
        return [];
    }

    private function obj2arr($obj)
    {
        $arr = [];
        foreach ($obj as $key => $value) {
            $arr[] = $key . '_' . $value;
        }
        return $arr;
    }

    private function origin($product)
    {
        if(!empty($product->origin_en)) {
            return trim($product->origin_en);
        }
        if(!empty($product->origins_en)) {
            return trim($product->origins_en);
        }
        if(!empty($product->origin)) {
            return trim($product->origin);
        }
        if(!empty($product->origins)) {
            return trim($product->origins);
        }
        return null;
    }

    private function packaging($product)
    {
        if(!empty($product->packaging_hierarchy_en)) {
            return $this->filterTags($product->packaging_hierarchy_en);
        }
        if(!empty($product->packaging_hierarchy)) {
            return $this->filterTags($product->packaging_hierarchy);
        }
        return [];
    }

    private function recycling($product)
    {
        if(!empty($product->packaging_recycling_tags_en)) {
            return $this->filterTags($product->packaging_recycling_tags_en);
        }
        if(!empty($product->packaging_recycling_tags)) {
            return $this->filterTags($product->packaging_recycling_tags);
        }
        return [];
    }

    private function stores($product)
    {
        if(!empty($product->stores_tags_en)) {
            return $this->filterTags($product->stores_tags_en);
        }
        if(!empty($product->stores_tags)) {
            return $this->filterTags($product->stores_tags);
        }
        return [];
    }

    private function traces($product)
    {
        if(!empty($product->traces_tags_en)) {
            return $this->filterTags($product->traces_tags_en);
        }
        if(!empty($product->traces_tags)) {
            return $this->filterTags($product->traces_tags);
        }
        return [];
    }
}
