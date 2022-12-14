<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\Address;
use App\Data\SearchData;
use App\Security\EmailVerifier;
use App\Service\Cart\CartService;
use App\Form\RegistrationFormType;
use App\Repository\AddressRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\OrderDetailsRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Address as E_address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    
    #[Route('/register', name: 'app_register')]
    public function register(CartService $cartService, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository, ProductRepository $productRepository, OrderDetailsRepository $orderDetails, ?UserInterface $user): Response
    {
        // The registration form
        $user = new User();
        $address = new Address();
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        $data = new SearchData();

        // Saves the user information if the form is valid
        if ($form->isSubmitted() && $form->isValid()) {
            // userName
            $user->setUserName($form->get('userName')->getData())
                ->setUserLastName($form->get('userLastName')->getData())
                ->setEmail($form->get('email')->getData())
            // encode the plain password
                ->setPassword(
                $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                )
                ->setBirthdate($form->get('birthdate')->getData())
                ->setPhoneNumber($form->get('phoneNumber')->getData())

                ->setPro($form->get('pro')->getData())
                ->setProCompanyName($form->get('proCompanyName')->getData())
                ->setProDuns($form->get('proDuns')->getData())
                ->setProJobPosition($form->get('proJobPosition')->getData())
                ->setAgreeTerms($form->get('agreeTerms')->getData());

            // Commenting 'data_class' => User::class, from $resolver->setDefaults in the formType allows to set several classes in the controller :
            $address->setName($form->get('address_name')->getData())
                    ->setCountry($form->get('address_country')->getData())
                    ->setZipcode($form->get('address_zipcode')->getData())
                    ->setCity($form->get('address_city')->getData())
                    ->setPathType($form->get('address_path_type')->getData())
                    ->setPathNumber($form->get('address_path_number')->getData());

            // $user->addAddress($adress); 
            // this equals to :
            $address->setUser($user);
            // and these bind the two classes

            // When the user registers, it grants the user the role 'ROLE_CLIENT'
            $user->setRoles(['ROLE_CLIENT','ROLE_USER']);

            // If the user validates the "Pro" checkbox, sets the user role to 'ROLE_PRO' and the VAT to '0.1'
            if ($user->isPro(true)) {
                $user->setRoles(['ROLE_PRO','ROLE_CLIENT','ROLE_USER']);
                $user->setVat('0.1');
            } 

            $date = new DateTime('@'.strtotime('now'));
            $user->setRegisterDate($date);

            $entityManager->persist($user);
            $entityManager->persist($address);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new E_address('info_noreply@muse.com', 'Muse MailBot'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            $this->addFlash('info', 'Veuillez v??rifier votre adresse mail. Un mail de demande de validation vous a ??t?? envoy??');
            return $this->redirectToRoute('login');
        }

        return $this->render('registration/register.html.twig', [
            'items'     => $cartService->getFullCart($orderDetails),
            'count'     => $cartService->getItemCount($orderDetails),
            'total'     => $cartService->getTotal($orderDetails),
            'registrationForm' => $form->createView(),
            'products'  => $productRepository->findSearch($data),
            'products2' => $productRepository->findAll(),
            'categories' => $categoryRepository->findAll(),
            'discount'  => $productRepository->findDiscount($data),
            'discount2' => $productRepository->findProductsDiscount(),
        ]);
    }


    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(AddressRepository $addressRepository, Request $request, TranslatorInterface $translator, MailerInterface $mailer, ?UserInterface $user): Response
    {       
        // Checks if the user is authenticated 
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, "Vous n'avez pas les autorisations n??cessaires pour acc??der ?? la page");

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Votre adresse mail a bien ??t?? v??rifi??e. Un mail de confirmation vous a ??t?? envoy??');

        // Fetches the user
        $user = $this->getUser();

        // Fetches the user addresses
        $addresses = $addressRepository->findByUser($user);

        $date = new DateTime('@'.strtotime('now'));
        $user->setRegisterDate($date);

        // Sends an email to the user
        $email = (new TemplatedEmail())
        ->from(new E_address('info_noreply@muse.com', 'Muse MailBot'))
        ->to($user->getEmail())
        ->subject('Bienvenue sur Muse!')
        ->htmlTemplate('registration/user_information_email.html.twig')
        ->context([
            'user' => $user,
            'addresses' => $addresses,
        ]);

        $mailer->send($email);

        return $this->redirectToRoute('app_home');
    }

}
