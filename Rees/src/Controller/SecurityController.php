<?php
/**
 * Security Controller Class Doc Comment
 *
 * @category Class
 * @package  Controller
 * @author   ReesTeam <reesTeam@gmail.com>
 * @license  GPL-2.0+  
 * @version  8.2.1
 * @author   "Rees' Team"
 * 
 * @link
 */
namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * SecurityController handles all actions related to user authentication and authorization.
 * 
 * @author  ReesTeam
 * @version 8.2.1
 * 
 */
class SecurityController extends AbstractController
{
    /**
    * Handles login requests and displays the login form.
    *
    * @param AuthenticationUtils $authenticationUtils 
    *                                                 An instance of the AuthenticationUtils class provided by the Symfony Security component.
    *
    * @return REsponse A response containing the rendered login template with last username and error passed as parameters.
    *
    * @Route(path="/login", name="app_login")
    */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            ['last_username' => $lastUsername, 'error' => $error]
        );
    }

    /**
     * Handles logout requests.
     *
     * @throws                LogicException if this method is called. This method can be blank as it will be intercepted by the logout key on your firewall.
     * @return                void
     * @Route(path="/logout", name="app_logout")
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank 
        - it will be intercepted by the logout key on your firewall.'
        );
    }
}
