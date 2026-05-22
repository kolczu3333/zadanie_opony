<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockItemRepository::class)]
#[ORM\Table(name: 'stock_item')]
#[ORM\UniqueConstraint(name: 'uniq_supplier_external_id', columns: ['supplier', 'external_id'])]
#[ORM\Index(name: 'idx_mpn', columns: ['mpn'])]
#[ORM\Index(name: 'idx_ean', columns: ['ean'])]
class StockItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $supplier;

    #[ORM\Column(name: 'external_id', length: 255)]
    private string $externalId;

    #[ORM\Column(length: 255)]
    private string $mpn;

    #[ORM\Column(name: 'producer_name', length: 255)]
    private string $producerName;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column]
    private int $quantity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplier(): string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getMpn(): string
    {
        return $this->mpn;
    }

    public function setMpn(string $mpn): static
    {
        $this->mpn = $mpn;

        return $this;
    }

    public function getProducerName(): string
    {
        return $this->producerName;
    }

    public function setProducerName(string $producerName): static
    {
        $this->producerName = $producerName;

        return $this;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): static
    {
        $this->ean = $ean;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
