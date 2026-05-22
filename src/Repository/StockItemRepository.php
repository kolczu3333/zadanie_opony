<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockItem>
 */
class StockItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockItem::class);
    }

    public function findOneBySupplierAndExternalId(string $supplier, string $externalId): ?StockItem
    {
        return $this->findOneBy([
            'supplier' => strtolower($supplier),
            'externalId' => $externalId,
        ]);
    }

    /**
     * @return StockItem[]
     */
    public function findByFilters(?string $mpn, ?string $ean): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($mpn !== null) {
            $qb->andWhere('s.mpn = :mpn')->setParameter('mpn', $mpn);
        }

        if ($ean !== null) {
            $qb->andWhere('s.ean = :ean')->setParameter('ean', $ean);
        }

        return $qb->getQuery()->getResult();
    }
}
