<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\User1Type;
use App\Data\SearchData;
use App\Service\Cart\CartService;
use App\Repository\UserRepository;
use App\Repository\AddressRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrderDetailsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/client')]
class ClientController extends AbstractController
{
    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(AddressRepository $addressRepository, CartService $cartService, User $user, CategoryRepository $categoryRepository,ProductRepository $productRepository, OrderDetailsRepository $orderDetails): Response
    {
        // Double access restriction for roles other than 'ROLE_CLIENT'
        if (!$this->isGranted('ROLE_CLIENT')) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('login');  
        }
        $this->denyAccessUnlessGranted('ROLE_CLIENT', null, "Vous n'avez pas les autorisations nécessaires pour accéder à la page");

        // The user, without the role 'ROLE_SALES', cannot access other users infos:
        if (!$this->isGranted('ROLE_SALES')) {
            if ($this->getUser()->getUserIdentifier() != $user->getUserIdentifier()) {
                $this->addFlash('error', 'Accès refusé');
                return $this->redirectToRoute('login');  
                $this->denyAccessUnlessGranted('ROLE_SALES', null, "Vous n'avez pas les autorisations nécessaires pour accéder à la page");
            }
        }
        $data = new SearchData();

        // Fetches the user addresses
        $addresses = $addressRepository->findByUser($user);

        // Needed for using CartService
        $cartService->setUser($user);

        // Access if the user account is verified
        if ($this->getUser()->isVerified()) {

            return $this->render('client/show.html.twig', [
                'items'     => $cartService->getFullCart($orderDetails),
                'count'     => $cartService->getItemCount($orderDetails),
                'total'     => $cartService->getTotal($orderDetails),
                'user'      => $user,
                'products'  => $productRepository->findSearch($data),
                'products2' => $productRepository->findAll(),
                'categories' => $categoryRepository->findAll(),
                'discount'  => $productRepository->findDiscount($data),
                'discount2' => $productRepository->findProductsDiscount(),
                'addresses' => $addresses,
        ]);
        } else {
            $this->addFlash(
                'error',
                "Merci de vérifier votre email afin d'accéder à vos informations personnelles"
            );
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);

        }
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(AddressRepository $addressRepository, CartService $cartService, Request $request, User $user, UserRepository $userRepository, CategoryRepository $categoryRepository,ProductRepository $productRepository, OrderDetailsRepository $orderDetails): Response
    {
        // Double access restriction for roles other than 'ROLE_CLIENT'
        if (!$this->isGranted('ROLE_CLIENT')) {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('login');  
        }
        $this->denyAccessUnlessGranted('ROLE_CLIENT', null, "Vous n'avez pas les autorisations nécessaires pour accéder à la page");

        // The user, without the role 'ROLE_SALES', cannot access other users infos:
        if (!$this->isGranted('ROLE_SALES')) {
            if ($this->getUser()->getUserIdentifier() != $user->getUserIdentifier()) {
                $this->addFlash('error', 'Accès refusé');
                return $this->redirectToRoute('login');  
                $this->denyAccessUnlessGranted('ROLE_SALES', null, "Vous n'avez pas les autorisations nécessaires pour accéder à la page");
            }
        }

        $data = new SearchData();

        // Fetches the user addresses
        $addresses = $addressRepository->findByUser($user);
        
        // Needed for using CartService
        $cartService->setUser($user);

        // Access if the user account is verified
        if ($this->getUser()->isVerified()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, "Vous n'avez pas les autorisations nécessaires pour accéder à la page");

            // The user information form
            $form = $this->createForm(User1Type::class, $user);
            $form->handleRequest($request);

            // Edits the user information if the form is valid
            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository->add($user, true);

                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }

            return $this->renderForm('client/edit.html.twig', [
                'items'     => $cartService->getFullCart($orderDetails),
                'count'     => $cartService->getItemCount($orderDetails),
                'total'     => $cartService->getTotal($orderDetails),
                'user'      => $user,
                'form'      => $form,
                'user'      => $user,
                'products'  => $productRepository->findSearch($data),
                'products2' => $productRepository->findAll(),
                'categories' => $categoryRepository->findAll(),
                'discount'  => $productRepository->findDiscount($data),
                'discount2' => $productRepository->findProductsDiscount(),
                'addresses' => $addresses,
        ]);
        } else {
            $this->addFlash(
                'error',
                "Merci de vérifier votre email afin d'accéder à vos informations personnelles"
            );

            $this->addFlash('success', 'Profil modifié!');

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }
    }

}