<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository")
 * @ORM\Table(
 *      name="oro_product_visibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_prod_vis_uidx",
 *              columns={"website_id", "product_id"}
 *          )
 *      }
 * )
 * @Config
 */
class ProductVisibility implements VisibilityInterface, WebsiteAwareInterface
{
    const CATEGORY = 'category';
    const CONFIG = 'config';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
    * @var Website
    *
    * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
    * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
    */
    protected $website;

    /**
     * @var Scope
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ScopeBundle\Entity\Scope")
     * @ORM\JoinColumn(name="scope_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $scope;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=true)
     */
    protected $visibility;

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @param Product $product
     * @return string
     */
    public static function getDefault($product)
    {
        return self::CATEGORY;
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Product $product
     * @return array
     */
    public static function getVisibilityList($product)
    {
        return [
            self::CATEGORY,
            self::CONFIG,
            self::HIDDEN,
            self::VISIBLE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntity()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setTargetEntity($product)
    {
        $this->setProduct($product);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     * @return $this
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
