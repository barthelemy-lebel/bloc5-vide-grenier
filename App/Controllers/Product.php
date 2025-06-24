<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use \Core\View;

/**
 * Product controller
 */
class Product extends \Core\Controller
{

    protected function sendMail($to, $subject, $message, $headers)
    {
        return mail($to, $subject, $message, $headers);
    }


    /**
     * Affiche la page d'ajout
     * @return void
     */
public function indexAction()
{
    $error = null;

    if (isset($_POST['submit'])) {
        try {
            $f = $_POST;

            // Vérifie que l'image a bien été envoyée
            if (empty($_FILES['picture']['name'])) {
                throw new \Exception("L'image est obligatoire.");
            }

            // Récupère l'utilisateur connecté
            $f['user_id'] = $_SESSION['user']['id'];

            // Enregistre l'article
            $id = Articles::save($f);

            // Upload de l'image
            $pictureName = Upload::uploadFile($_FILES['picture'], $id);
            Articles::attachPicture($id, $pictureName);

            // Redirection vers la page de l'annonce
            header('Location: /product/' . $id);
            exit;

        } catch (\Exception $e) {
            // Capture propre de l'erreur
            $error = $e->getMessage();
        }
    }

    // Affichage du formulaire avec ou sans erreur
    View::renderTemplate('Product/Add.html', [
        'error' => $error
    ]);
}


    /**
     * Affiche la page d'un produit
     * @return void
     */
    public function showAction()
{
    $id = $this->route_params['id'];

    try {
        Articles::addOneView($id);
        $suggestions = Articles::getSuggest();
        $article = Articles::getOne($id);
        $article = $article[0];

        // Formulaire de contact
        if (isset($_POST['send_message'])) {
            $message = trim($_POST['message'] ?? '');

            if (!empty($message)) {
                $to = $article['email'];
                $subject = "Message concernant votre annonce : " . $article['name'];
                $headers = "From: contact@votresite.fr\r\n";
                $headers .= "Reply-To: contact@votresite.fr\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                $this->sendMail($to, $subject, $message, $headers);


                $success = "Votre message a bien été envoyé à " . htmlspecialchars($article['username']);
            } else {
                $error = "Veuillez saisir un message.";
            }
        }

    } catch(\Exception $e){
        var_dump($e);
    }

    View::renderTemplate('Product/Show.html', [
        'article' => $article,
        'suggestions' => $suggestions,
        'success' => $success ?? null,
        'error' => $error ?? null,
    ]);
}

}
