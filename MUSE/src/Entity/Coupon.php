<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CouponRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ApiResource(
    normalizationContext:["groups"=>"read:coupon"]
)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["read:coupon"])]
    private ?int $id = null;

    #[ORM\Column(length: 15, nullable: true)]
    #[Groups(["read:coupon", "read:cart"])]
    private ?string $code = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 3, nullable: true)]
    #[Groups(["read:coupon"])]
    private ?string $discountRate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["read:coupon"])]
    private ?bool $validated = null;

    #[ORM\OneToMany(mappedBy: 'coupon', targetEntity: Cart::class)]
    #[Groups(["read:coupon"])]
    private Collection $cart;

    public function __construct()
    {
        $this->cart = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDiscountRate(): ?string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(?string $discountRate): self
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(?bool $validated): self
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCart(): Collection
    {
        return $this->cart;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->cart->contains($cart)) {
            $this->cart->add($cart);
            $cart->setCoupon($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        if ($this->cart->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getCoupon() === $this) {
                $cart->setCoupon(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->code;
    }

}
