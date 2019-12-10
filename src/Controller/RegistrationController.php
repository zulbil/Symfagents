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
use Symfony\Component\Validator\Constraints\File;

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
            "email"         => $user->getEmail()
        );

        $formProfil = $this->createFormBuilder($userProfil)
                           ->add('file', FileType::class, [
                               'constraints' => [
                                   new File([
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


}
