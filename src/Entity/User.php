<?php

namespace App\Entity;

use DateTime;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(
 * fields={"email"},
 * message="Another user is registered with this email"
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must enter a name")
     * @Assert\Length(min=4, minMessage="You must enter a name of at least 4 characters")
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Email(message="Please enter a valid email")
     */
    private $email;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $email_verified_at;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $is_admin;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min=4, minMessage="Your password must at least be 4 characters long")
     */
    private $password;

    /**
     *
     * @Assert\EqualTo(propertyPath="password", message="Password confirmation do not correspond")
     * 
     * @var string
     */
    public $passwordConfirm;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remember_token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $last_connected_at;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Historic", mappedBy="user", cascade={"remove"}, fetch="EAGER")
     */
    private $historics;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Quiz", mappedBy="author")
     */
    private $quizzes;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": 0})
     */
    private $email_is_verified = 0;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Role", mappedBy="users")
     */
    private $UserRoles;

    /**
     * 
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function createSlug()
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name);
        return $this;
    }

    /**
     * 
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function updateTime()
    {
        $this->updated_at = new \DateTime('now');
        return $this;
    }

    /**
     * 
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function setAdminDefaultToFalse()
    {
        if ($this->is_admin == null) {
            $this->is_admin = 0;
            return $this;
        }
    }

    public function __construct()
    {
        $this->historics = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->UserRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->email_verified_at;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $email_verified_at = null): self
    {
        $this->email_verified_at = $email_verified_at;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->is_admin;
    }

    public function setIsAdmin(?bool $is_admin): self
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setRememberToken(?string $remember_token): self
    {
        $this->remember_token = $remember_token;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist
     * 
     * @return void
     */
    public function setCreatedAt()
    {
        if (empty($this->created_at)) {
            $this->created_at = new \DateTime();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * @ORM\PrePersist
     * 
     * @return void
     */
    public function setUpdatedAt()
    {
        $this->updated_at = new \DateTime();
    }

    public function getLastConnectedAt(): ?\DateTimeInterface
    {
        return $this->last_connected_at;
    }

    public function setLastConnectedAt(?\DateTimeInterface $last_connected_at = null)
    {
        if ($last_connected_at == null) {
            $this->last_connected_at = new \DateTime('now');
        } else {
            $this->last_connected_at = $last_connected_at;
        }
        return $this;
    }

    /**
     * @return Collection|Historic[]
     */
    public function getHistorics(): Collection
    {
        return $this->historics;
    }

    public function addHistoric(Historic $historic): self
    {
        if (!$this->historics->contains($historic)) {
            $this->historics[] = $historic;
            $historic->setUserId($this);
        }

        return $this;
    }

    public function removeHistoric(Historic $historic): self
    {
        if ($this->historics->contains($historic)) {
            $this->historics->removeElement($historic);
            // set the owning side to null (unless already changed)
            if ($historic->getUserId() === $this) {
                $historic->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Quiz[]
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes[] = $quiz;
            $quiz->setAuthor($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        if ($this->quizzes->contains($quiz)) {
            $this->quizzes->removeElement($quiz);
            // set the owning side to null (unless already changed)
            if ($quiz->getAuthor() === $this) {
                $quiz->setAuthor(null);
            }
        }

        return $this;
    }


    /**
     * Implementing UserInterface methods
     */
    //Get All Roles, not like getUserRoles who gets only the role of the user;
    public function getRoles()
    {
        //Return only string with role titles (from the database) so it is available for Symfony.
        $roles = $this->UserRoles->map(function ($role) {
            return $role->getTitle();
        })->toArray();

        // if ( $this->is_admin ) {
        //     $roles[] = 'ROLE_ADMIN';
        // }

        $roles[] = 'ROLE_USER'; //add this so it is by default
        // dd($roles);
        return $roles;
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
    }

    public function getEmailIsVerified(): ?bool
    {
        return $this->email_is_verified;
    }

    public function setEmailIsVerified(?bool $email_is_verified): self
    {
        $this->email_is_verified = $email_is_verified;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getUserRoles(): Collection
    {
        return $this->UserRoles;
    }

    public function addUserRoles(Role $UserRoles): self
    {
        if (!$this->UserRoles->contains($UserRoles)) {
            $this->UserRoles[] = $UserRoles;
            $UserRoles->addUser($this);
        }

        return $this;
    }

    public function removeUserRoles(Role $UserRoles): self
    {
        if ($this->UserRoles->contains($UserRoles)) {
            $this->UserRoles->removeElement($UserRoles);
            $UserRoles->removeUser($this);
        }

        return $this;
    }
}
