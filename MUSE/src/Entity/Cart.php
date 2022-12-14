<?php

namespace App\Entity;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CartRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ApiResource(
    normalizationContext:["groups"=>"read:cart"]
)]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', unique: true)]
    #[Groups(["read:cart"])]
    private $id = null;

    #[ORM\Column]
    #[Groups(["read:user", "read:cart"])]
    private ?string $clientOrderId = null;

    #[ORM\Column]
    #[Groups(["read:cart"])]
    private ?bool $validated = false;

    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: OrderDetails::class, orphanRemoval: true)]
    #[Groups(["read:cart"])]
    private Collection $orderDetails;

    #[ORM\ManyToOne(inversedBy: 'carts', fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:cart"])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["read:cart"])]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\Column]
    #[Groups(["read:cart"])]
    private ?bool $shipped = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["read:cart"])]
    private ?\DateTimeInterface $shipmentDate = null;

    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(["read:cart"])]
    private ?string $carrier = null;

    #[ORM\Column(length: 25, nullable: true)]
    #[Groups(["read:cart"])]
    private ?string $carrierShipmentId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Groups(["read:cart"])]
    private ?string $total = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[Groups(["read:cart"])]
    private ?Address $billingAddress = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[Groups(["read:cart"])]
    private ?Address $deliveryAddress = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 3)]
    #[Groups(["read:cart"])]
    private ?string $additionalDiscountRate = '0';

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(["read:cart"])]
    private ?string $invoice = null;

    #[ORM\ManyToOne(inversedBy: 'cart')]
    #[Groups(["read:cart"])]
    private ?Coupon $coupon = null;


    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setClientOrderId(string $clientOrderId): self
    {
        $this->clientOrderId = $clientOrderId;

        return $this;
    }
    
    public function getClientOrderId(): ?string
    {
        return $this->clientOrderId;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetails>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetails $orderDetail): self
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails[] = $orderDetail;
            $orderDetail->setCart($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetails $orderDetail): self
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getCart() === $this) {
                $orderDetail->setCart(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(?\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function isShipped(): ?bool
    {
        return $this->shipped;
    }

    public function setShipped(bool $shipped): self
    {
        $this->shipped = $shipped;

        return $this;
    }

    public function getShipmentDate(): ?\DateTimeInterface
    {
        return $this->shipmentDate;
    }

    public function setShipmentDate(?\DateTimeInterface $shipmentDate): self
    {
        $this->shipmentDate = $shipmentDate;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): self
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getCarrierShipmentId(): ?string
    {
        return $this->carrierShipmentId;
    }

    public function setCarrierShipmentId(?string $carrierShipmentId): self
    {
        $this->carrierShipmentId = $carrierShipmentId;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(?string $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): self
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getDeliveryAddress(): ?Address
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?Address $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getAdditionalDiscountRate(): ?string
    {
        $additionalDiscountRate = $this->additionalDiscountRate;
        $additionalDiscountRate = '0';
        return $this->additionalDiscountRate;
    }

    public function setAdditionalDiscountRate(?string $additionalDiscountRate): self
    {
        $this->additionalDiscountRate = $additionalDiscountRate;

        return $this;
    }

    public function getInvoice(): ?string
    {
        return $this->invoice;
    }

    public function setInvoice(?string $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function __toString()
    {
        return $this->clientOrderId;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->coupon = $coupon;

        return $this;
    }

}
