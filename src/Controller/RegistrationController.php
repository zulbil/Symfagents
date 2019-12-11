<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
//use Symfony\Component\Validator\Constraints\File;


class RegistrationController extends AbstractController
{
    private $session; 
    private $mailer;
    private $entityManager;

    public function __construct(SessionInterface $session, MailerInterface $mailer, EntityManagerInterface $entityManager) {
        $this->session  =   $session; 
        $this->mailer   =   $mailer;
        $this->entityManager    =   $entityManager;
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, 
            MailerInterface $mailer)
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(['ROLE_USER']);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendEmail($user, $form->get('plainPassword')->getData()); 

            return $this->redirectToRoute('show_message');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    public function sendEmail(User $user, $password)
    {
        $email = (new TemplatedEmail())
                    ->from('jkazdev@gmail.com')
                    //->from('joel.khang@hologram.cd')
                    ->to($user->getEmail())
                    ->subject('Bienvenue à SymfAgent')
                    ->htmlTemplate('app/mail.html.twig')
                    ->context([
                        'expiration_date' => new \DateTime('+7 days'),
                        'user' => $user, 
                        'plain_password' => $password
                    ]);

        $this->mailer->send($email);
    }

    /**
     * @Route("/activate/user/{id}", name="app_activate")
     * @param Request $request
     * @param GuardAuthenticatorHandler $guardHandler
     * @param LoginFormAuthenticator $authenticator
     * @param $id
     * @return Response
     */
    public function activate(Request $request, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator,$id) {
        $entityManager  = $this->getDoctrine()->getManager(); 
        $user           = $entityManager->getRepository(User::class)->find($id); 

        if (!$user) {
            throw $this->createNotFoundException(
                'No User found for id '.$id
            );
        }

        $user->setStatut(1);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('app/activate-user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/activate/login/{id}", name="app_login_activate")
     */
    public function loginAfterActivation(Request $request, GuardAuthenticatorHandler $guardHandler, 
    LoginFormAuthenticator $authenticator, $id) {

        $entityManager  = $this->getDoctrine()->getManager(); 
        $user           = $entityManager->getRepository(User::class)->find($id);
         
        return $guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $authenticator,
            'main' // firewall name in security.yaml
        );
    }

    /**
     * @Route("/message/confirmation", name="show_message")
     */
    public function showMessage() {
        return $this->render('app/message-consultation.html.twig');
    }

    /**
     * @Route("/user/profil", name="user_profile")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @param FileUploader $fileuploader
     * @return Response
     */
    public function userProfile (Request $request, FileUploader $fileuploader) {
        $user = $this->getUser();
        $data['user'] = $user;
        $data['page'] = "Mon Profil";

        $userProfil     = array(
            "prenom"        => $user->getPrenom(),
            "nom"           => $user->getNom(),
            "postnom"       => $user->getPostnom(),
            "fonction"      => $user->getfonction(),
            "email"         => $user->getEmail(),
            //"photo"         => $user->setPhoto(new File($this->getParameter('uploads_directory').'/'.$user->getPhoto()))
        );

        $formProfil = $this->createFormBuilder($userProfil)
                           ->add('file', FileType::class, [
                               'constraints' => [
                                   new \Symfony\Component\Validator\Constraints\File([
                                       'maxSize' => '5024k',
                                       'mimeTypes' => [
                                           'image/jpg',
                                           'image/png',
                                           'image/jpeg',
                                       ],
                                       'mimeTypesMessage' => 'Ce fichier n\'est pas une image valide',
                                   ])
                               ]
                           ])
                           ->add('prenom', TextType::class, [
                               'label' => 'Prenom'
                           ])
                           ->add('nom', TextType::class, [
                               'label' => 'Nom'
                           ])
                           ->add('postnom', TextType::class, [ 'label' => 'Postnom'])
                           ->add('fonction', TextType::class, [
                               'label' => 'Fonction',
                               'attr' => [ 'disabled' => true ]
                           ])
                           ->add('email', EmailType::class, [ 'label' => 'Email' ])
                           ->add('save', SubmitType::class, [
                               'label' => 'Enregistrer',
                               'attr'   => [ 'class' => 'btn btn-success']
                           ])
                           ->add('reset', ResetType::class, ['label' => 'Reset', 'attr' => ['class' => 'btn btn-secondary'] ])
                           ->getForm();

        $formProfil->handleRequest($request);

        if ($formProfil->isSubmitted() && $formProfil->isValid()) {
            /** @var UploadedFile $brochureFile */
            $photo = $formProfil['file']->getData();
            $user = $this->entityManager->getRepository(User::class)->find($this->getUser()->getId());

            if ($photo) {
                $photoName = $fileuploader->upload($photo);
                $photoName = "/uploads/images/$photoName";
                $user->setPhoto($photoName);
            }
            $user->setNom($formProfil['nom']->getData());
            $user->setPrenom($formProfil['prenom']->getData());
            $user->setPostnom($formProfil['postnom']->getData());

            $this->entityManager->flush();

            $this->redirectToRoute('user_profile');
        }
        $data['form'] = $formProfil->createView();
        return $this->render('app/profile.html.twig', $data );
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/user/account", name="user_account")
     */
    public function userAccountSettings(Request $request)
    {
        $data = array();
        $data['page'] = "Paramètres du compte";
        $data['user'] = $this->entityManager->getRepository(User::class)->find($this->getUser()->getId());


        return $this->render('app/account-settings.html.twig', $data );
    }


    /**
     * @param Request $request
     * @return Response
     * @Route("/user/password", name="user_password")
     */
    public function userPasswordSettings(Request $request, LoginFormAuthenticator $authenticator, UserPasswordEncoderInterface $passwordEncoder)
    {
        $data = array();
        $data['page'] = "Changer le mot de passe";
        $data['user'] = $this->entityManager->getRepository(User::class)->find($this->getUser()->getId());

        // Create a form
        $passwordtab = array(
            "password" => '',
            "new_password" => ''
        );
        $form_password = $this->createFormBuilder($passwordtab)
            ->add('password', PasswordType::class, ['label' => 'Votre ancien mot de passe'])
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'type' => PasswordType::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entre votre nouveau mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit avoir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'first_options' => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Vérifiez votre mot de passe']
            ])
            ->add('save', SubmitType::class, ['label' => 'Changer le mot de passe', 'attr' => ['class' => 'btn btn-brand btn-bold']])
            ->add('reset', ResetType::class, ['label' => 'Annuler', 'attr' => ['class' => 'btn btn-secondary']])
            ->getForm();

        $form_password->handleRequest($request);

        if ($form_password->isSubmitted() && $form_password->isValid()) {
            $old_password = $form_password->get('password')->getData();
            $credentials['password'] = $old_password;
            $user = $this->entityManager->getRepository(User::class)->find($this->getUser()->getId());

            if($authenticator->checkCredentials($credentials, $user)) {
              $new_password = $form_password->get('plainPassword')->getData();
              $user->setPassword(
                  $passwordEncoder->encodePassword(
                      $user,
                      $new_password
                  )
              );
            }

        }

        $data['form'] = $form_password->createView();

        return $this->render('app/password-settings.html.twig', $data );
    }



}
