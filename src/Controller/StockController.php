<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\GetStocksQuery;
use App\Exception\InvalidStockSearchQueryException;
use App\Serializer\StockItemSerializer;
use App\Service\StockSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StockController extends AbstractController
{
    public function __construct(
        private readonly StockSearchService $stockSearchService,
        private readonly StockItemSerializer $stockItemSerializer,
    ) {
    }

    #[Route('/get-stocks', name: 'get_stocks', methods: ['GET'])]
    public function getStocks(Request $request): JsonResponse
    {
        $query = new GetStocksQuery(
            self::normalizeQueryParam($request->query->get('mpn')),
            self::normalizeQueryParam($request->query->get('ean')),
        );

        try {
            $items = $this->stockSearchService->search($query);
        } catch (InvalidStockSearchQueryException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->stockItemSerializer->serializeMany($items));
    }

    private static function normalizeQueryParam(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
