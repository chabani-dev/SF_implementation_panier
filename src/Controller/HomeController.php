<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Form\LigneCommandeType;
use App\Form\ValidatePanierType;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\LigneCommandeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    /**
     * Cette route est accessible à tout le monde, mais nous devrons conditionner l'affichage du formulaire au fait d'être un client connecté (ce sera fait directement dans le template)
     * 
     * @Route("/single_produit/{id}", name="app_single_produit", methods={"GET", "POST"})
     */
    public function single_produit(Request $request, Produit $produit, CommandeRepository $commandeRepository, LigneCommandeRepository $ligneCommandeRepository): Response
    {
        // on va intégrer à ce controller le formulaire qui permettra d'ajouter un item dans le panier, après avoir renseigné une quantité

        $ligneCommande = new LigneCommande();
        // création du formulaire
        $form = $this->createForm(LigneCommandeType::class, $ligneCommande);
        $form->handleRequest($request);

        // récupération du 'panier' pour renseigner la ligne de commande que nous allons créer
        $panier = $commandeRepository->findOneBy([
            'statut' => 'panier',
            'client' => $this->getUser(),
        ]);

        if($form->isSubmitted() && $form->isValid()) {
            // on renseigne automatiquement ces 2 propriétés de la ligne de commande (le produit et la commande associés)
            $ligneCommande->setProduit($produit);
            $ligneCommande->setCommande($panier);

            // on persiste les données
            $ligneCommandeRepository->add($ligneCommande, true);

            // on redirige vers le panier
            return $this->redirectToRoute('app_panier', [], Response::HTTP_SEE_OTHER);
        }

        
        return $this->renderForm('home/single_produit.html.twig', [
            'produit' => $produit,
            'form' => $form,
            'commande' => $panier,
            'ligne_commande' => $ligneCommande,
            'button_label' => 'Ajouter au panier',
        ]);
    }

    /**
     * Cette route ne doit être accessible qu'aux clients qui sont connectés
     * 
     * @IsGranted("ROLE_USER")
     * @Route("/panier", name="app_panier", methods={"GET", "POST"})
     */
    public function panier(Request $request, CommandeRepository $commandeRepository): Response
    {
        // on va récupérer le panier pour l'afficher, c'est à dire la commande qui a le statut 'panier et qui appartient à l'utilisateur connecté
        $panier = $commandeRepository->findOneBy([
            'statut' => 'panier',
            'client' => $this->getUser(),
        ]);

        // on va mettre en place un bouton qui permettra de valider le panier, càd modifier le statut du panier pour lui donner le statut 'en cours de préparation'
        $form = $this->createForm(ValidatePanierType::class, $panier);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $date = new \DatetimeImmutable('now');

            // on change le statut du panier
            $panier->setStatut('en cours de préparation');
            // on met à jour le statut, donc il faut mettre aussi à jour la propriété 'updatedAt'
            $panier->setUpdatedAt($date);
            $commandeRepository->add($panier, true);

            // n'oublions pas de créer un nouveau panier : une nouvelle commande qui appartient au client connecté et qui a le statut 'panier'
            $commande = new Commande();
            $commande->setClient($this->getUser());
            $commande->setStatut('panier');
            $commande->setCreatedAt($date);
            $commande->setUpdatedAt($date);

            // on persiste les données
            $commandeRepository->add($panier, true);
            $commandeRepository->add($commande, true);

            // on redirige vers l'accueil
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);

        }
        
        
        return $this->renderForm('home/panier.html.twig', [
            'panier' => $panier,
            'form' => $form,
        ]);
    }
}
