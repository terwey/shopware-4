<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Models
 * @subpackage Shop
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Heiner Lohaus
 * @author     $Author$
 */

namespace Shopware\Models\Shop;
use Shopware\Components\Model\ModelEntity,
    Doctrine\ORM\Mapping AS ORM;

/**
 * Default Currency Model Entity
 *
 * todo@all: Documentation
 *
 * @ORM\Table(name="s_core_currencies")
 * @ORM\Entity
 */
class Currency extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $currency
     *
     * @ORM\Column(name="currency", type="string", length=255, nullable=false)
     */
    private $currency;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer $standard
     *
     * @ORM\Column(name="standard", type="integer", nullable=false)
     */
    private $default = false;

    /**
     * @var float $factor
     *
     * @ORM\Column(name="factor", type="float", nullable=false)
     */
    private $factor;

    /**
     * @var string $symbol
     *
     * @ORM\Column(name="templatechar", type="string", length=255, nullable=false)
     */
    private $symbol = '';

    /**
     * @var integer $symbolPosition
     *
     * @ORM\Column(name="symbol_position", type="integer", nullable=false)
     */
    private $symbolPosition = 0;

    /**
     * @var integer $position
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    private $position = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Currency
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set default
     *
     * @param integer $default
     * @return Currency
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Get default
     *
     * @return integer
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set factor
     *
     * @param float $factor
     * @return Currency
     */
    public function setFactor($factor)
    {
        $this->factor = $factor;
        return $this;
    }

    /**
     * Get factor
     *
     * @return float
     */
    public function getFactor()
    {
        return $this->factor;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     * @return Currency
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;
        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set symbolPosition
     *
     * @param integer $symbolPosition
     * @return Currency
     */
    public function setSymbolPosition($symbolPosition)
    {
        $this->symbolPosition = $symbolPosition;
        return $this;
    }

    /**
     * Get symbolPosition
     *
     * @return integer
     */
    public function getSymbolPosition()
    {
        return $this->symbolPosition;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return Currency
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->getCurrency();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $options = array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'currency' => $this->getCurrency(),
            'factor' => $this->getFactor()
        );
        if($this->getSymbol()) {
            $options['symbol'] = $this->getSymbol();
        }
        if($this->getSymbolPosition() > 0) {
            $options['position'] = $this->getSymbolPosition();
        }
        return $options;
    }
}
