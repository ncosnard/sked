<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WorkInputRepository")
 */
class WorkInput
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="workInputs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="workInputs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\Column(type="integer")
     */
    private $weeknum;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $mon;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $tue;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $wed;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $thu;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $fri;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getWeeknum(): ?int
    {
        return $this->weeknum;
    }

    public function setWeeknum(int $weeknum): self
    {
        $this->weeknum = $weeknum;

        return $this;
    }

    public function getMon()
    {
        return $this->mon;
    }

    public function setMon($mon): self
    {
        $this->mon = $mon;

        return $this;
    }

    public function getTue()
    {
        return $this->tue;
    }

    public function setTue($tue): self
    {
        $this->tue = $tue;

        return $this;
    }

    public function getWed()
    {
        return $this->wed;
    }

    public function setWed($wed): self
    {
        $this->wed = $wed;

        return $this;
	}

    public function getThu()
    {
        return $this->thu;
    }

    public function setThu($thu): self
    {
        $this->thu = $thu;

        return $this;
	}

    public function getFri()
    {
        return $this->fri;
    }

    public function setFri($fri): self
    {
        $this->fri = $fri;

        return $this;
    }
    // add your own fields
}