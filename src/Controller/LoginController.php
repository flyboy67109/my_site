<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function index(AuthenticationUtils $authenticationUtils, Request $request)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        //login failure count
        $failure_count = 1;
        $cookie = $request->cookies;
        $cookie->remove('login');//for testing only
        if (!is_null($cookie->get('login')) && is_numeric($cookie->get('login'))){
            $failure_count = $cookie->get('login');
        }

        if ($failure_count == 3){
            return $this->redirectToRoute('homepage');
        }
        $login = [];

        $form = $this->createFormBuilder($login)
            ->add('username', TextType::class)
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $request->get('form');

            // fetch username from users table
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(User::class)->findOneBy(['username'=>$data['username']]);
            if (!empty($user)){
                // check password
                if (password_verify($data['password'],$user->getPassword())){

                    // get session id and add to cookie
                    $session = $request->getSession();
                    $session->set("username",$data['username']);

                    $cookie->remove('login');
                    setcookie('login',$session->getId(),time()+3600);

                    // update login to current date
                    $user->setLogin(date("Y-m-d H:i:s", time()));
                    $em->flush();
                    // redirect to previous page
                    return $this->redirectToRoute('admin');

                }else{
                    $this->addFlash("warning","Username/password does not match");
                    setcookie('login',++$failure_count);
                }
            }else{
                $this->addFlash("warning","Username/password does not match");
                setcookie('login',++$failure_count);
            }
        }

        return $this->render('login/index.html.twig', [
            'form'  =>  $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("addUser", name="addUser")
     * @return Response
     */
    public function addUser(Request $request)
    {
        // restrict access to logged in user only
        $session = $request->getSession();
        $cookie = $request->cookies;

        if ($session->getId() != $cookie->get('login')){
            return $this->redirectToRoute('login');
        }

        // list of users on the system
        $user_list = $this->getDoctrine()->getRepository(User::class)->findAll();

        $login = [];

        $form = $this->createFormBuilder($login)
            ->add('username', TextType::class)
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //array of data from form
            $data = $request->get('form');

            // user interface manager
            $em = $this->getDoctrine()->getManager();
            $user = new User();
            //encrypt password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // store user in database
            $user->setPassword($data['password']);
            $user->setUsername($data['username']);
            $user->setLogin(date("Y-m-d H:i:s", time()));

            $em->persist($user);
            $em->flush();
        }

        return $this->render('login/addUser.html.twig', [
            'form'  =>  $form->createView(),
            'user_list' =>  $user_list
        ]);
    }
}
