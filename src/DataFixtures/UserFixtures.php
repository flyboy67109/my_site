<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEcooder;

    public function __construct(UserPasswordEncoderInterface $passwordEcooder)
    {
        $this->passwordEcooder = $passwordEcooder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        // $product = new Product();
        // $manager->persist($product);

        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            'the_new_password'
        ));

        $manager->flush();
    }

    public function setPassword($user,$password)
    {
        return $this->passwordEcooder->encodePassword(
            $user,
            $password
        );
    }

    public function checkPassword($user, $password)
    {
        return $this->passwordEcooder->isPasswordValid(
            $user,
            $password
        );
    }
}
