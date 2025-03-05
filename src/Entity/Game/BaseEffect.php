<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'base_effect')]
#[ORM\Entity(repositoryClass: 'App\Repository\Game\BaseEffectRepository')]
class BaseEffect
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[ORM\Column(name: 'duration', type: 'integer')]
    private $duration;

    #[ORM\Column(name: 'frequency', type: 'integer', nullable: true)]
    private $frequency;

    #[ORM\Column(name: 'damage', type: 'integer', nullable: true)]
    private $damage;

    #[ORM\Column(name: 'heal', type: 'integer', nullable: true)]
    private $heal;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return BaseEffect
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
     * Set duration
     *
     * @param integer $duration
     *
     * @return BaseEffect
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set frequency
     *
     * @param integer $frequency
     *
     * @return BaseEffect
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Set damage
     *
     * @param integer $damage
     *
     * @return BaseEffect
     */
    public function setDamage($damage)
    {
        $this->damage = $damage;

        return $this;
    }

    /**
     * Get damage
     *
     * @return int
     */
    public function getDamage()
    {
        return $this->damage;
    }

    /**
     * Set heal
     *
     * @param integer $heal
     *
     * @return BaseEffect
     */
    public function setHeal($heal)
    {
        $this->heal = $heal;

        return $this;
    }

    /**
     * Get heal
     *
     * @return int
     */
    public function getHeal()
    {
        return $this->heal;
    }

}
