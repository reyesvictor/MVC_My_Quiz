<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class UpdatePassword
{
    /**
     * @Assert\NotBlank(message="You must enter a password")
     * @Assert\Length(min=4, minMessage="Your password must at least be 4 characters long")
     */
    private $oldPassword;

    /**
     * @Assert\NotBlank(message="You must enter a password")
     * @Assert\Length(min=4, minMessage="Your new password must at least be 4 characters long")
     */
    private $newPassword;

    /**
     * @Assert\NotBlank(message="You must enter a password")
     * @Assert\EqualTo(propertyPath="newPassword", message="Password confirmation do not correspond")
     */
    private $confirmNewPassword;

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(string $oldPassword): self
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): self
    {
        $this->newPassword = $newPassword;

        return $this;
    }

    public function getConfirmNewPassword(): ?string
    {
        return $this->confirmNewPassword;
    }

    public function setConfirmNewPassword(string $confirmNewPassword): self
    {
        $this->confirmNewPassword = $confirmNewPassword;

        return $this;
    }
}
