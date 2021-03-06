<?php
namespace Controller;

use Core\Action;
use Model\Actualite;

class Admin extends Action
{

    public function index($params = array())
    {
        $params = $this->news->getList(calculPage(), true, NbParPageAdmin, 'actualité');
        $params['contents'] = $this->content->getList();
        $params['UrlEmailAdmin'] = getUrlImgEmail(EmailAdmin);
        $params['NbParPage'] = NbParPage;
        $params['NbParPageAdmin'] = NbParPageAdmin;
        $params['EmailAdmin'] = EmailAdmin;
        $params['DisplayHelp'] = DisplayHelp;
        
        $this->response->addVar('metaTitle', 'Administration');
        return $this->response->render('admin/index', $params);
    }

    public function update($params = array())
    {
        // Update
        if ($this->request->isParam('sendNews')) {
            if (false !== ($id = $this->news->update($this->request->getValues()))) {
                // Cache obsolete
                $this->cacheManager->resetCache();
                $this->response->addFlash('Voilà une news ajoutée avec succès, bien joué !', OK);
                return $this->response->redirect('administration-update.html?id=' . $id . '#administrationPage');
            }
            $this->response->addFlash('Erreur lors de l\'édition de la news.', ERREUR);
            $params['Actualite'] = new Actualite($this->request->getValues());
            return $this->response->render('admin/update', $params);
        }
        
        // Display
        $id = $this->request->getParam('id', 'int');
        if (0 == $id) {
            $params['Actualite'] = new Actualite();
        }
        else {
            $params['Actualite'] = $this->news->findActualiteById($id, true);
            if (null == $params['Actualite']) {
                $this->response->addFlash('La news ' . $id . ' n\'existe pas. Si c\'est un problème, il va falloir contacter un administrateur.', NEUTRE);
                return $this->response->redirect('administration.html');
            }
        }
        return $this->response->render('admin/update', $params);
    }

    public function delete($params = array())
    {
        $id = $this->request->getParam('id', 'int');
        if ($this->news->remove($id)) {
            // Cache obsolete
            $this->cacheManager->resetCache();
            $this->response->addFlash('La news ' . $id . ' a été supprimée. Bien joué !', OK);
            return $this->response->redirect('administration.html');
        }
        $this->response->addFlash('Arg, ça ne marche pas. La news ' . $id . ' n\'a pas été supprimée.', ERREUR);
        return $this->response->redirect('administration.html');
    }

    public function purgeCache()
    {
        $this->cacheManager->resetCache(true);
        $this->response->addFlash('Cache supprimé ! On refait une partie de cache-cache ?', OK);
        return $this->response->redirect('administration.html');
    }

    public function updatePage($params = array())
    {
        $url = $this->request->getParam('url', 'string');
        if ($this->request->isPost()) {
            if ($this->content->update($this->request->getValues())) {
                $this->response->addFlash('Voilà une page éditée avec succès, bien joué !', OK);
                return $this->response->redirect('administration-update-page.html?url=' . $url);
            }
            $this->response->addFlash('Erreur lors de l\'édition de la page.', ERREUR);
            $params['content'] = $this->request->getValues();
            return $this->response->render('admin/update-page', $params);
        }
        
        $params['content'] = $this->content->getByUrl($url);
        if (null == $params['content']) {
            $this->response->addFlash('La page "' . $url . '" n\'existe pas. Si c\'est un problème, il va falloir contacter un administrateur.', NEUTRE);
            return $this->response->redirect('administration.html');
        }
        return $this->response->render('admin/update-page', $params);
    }

    public function updateData($params = array())
    {
        if ($this->request->isParam('sendData')) {
            // Generate content
            $data = '<?php' . "\n";
            if (null != $_POST['nbParPage'] && 0 != $_POST['nbParPage']) {
                $data .= 'define(\'NbParPage\', ' . parserI($_POST['nbParPage']) . ');' . "\n";
            }
            if (null != $_POST['nbParPageAdmin'] && 0 != $_POST['nbParPageAdmin']) {
                $data .= 'define(\'NbParPageAdmin\', ' . parserI($_POST['nbParPageAdmin']) . ');' . "\n";
            }
            $data .= 'define(\'DisplayHelp\', ' . parserI(@$_POST['displayHelp']) . ');' . "\n";
            if (null != $_POST['emailAdmin'] && NULL != $_POST['emailAdmin']) {
                $data .= 'define(\'EmailAdmin\', \'' . parserS($_POST['emailAdmin']) . '\');' . "\n";
            }
            $data .= "\n";
            
            if (false != file_put_contents(ParameterFilePath, $data)) {
                $this->response->addFlash('Paramètres bien enregistrés, super !', OK);
                return $this->response->redirect('administration.html');
            }
        }
        // Error
        $this->response->addFlash('Arg, impossible de mettre à jour les paramètres. Désolé, mais il va falloir en parler avec
un administrateur.', ERREUR);
        return $this->response->redirect('administration.html');
    }

    public function mailing($params = array())
    {
        // /!\ Ce code fourni par 1&1, notre hébergeur, ne fonctionne pas... Dommage.
        $params = array();
        $params['ListesDiffusion'] = array(
            'flambeaux@cepsaintmaur.com',
            'gdj@cepsaintmaur.com',
            'musique@cepsaintmaur.com'
        );
        $params['UrlCourant'] = 'administration-listes-diffusion.html';
        $this->response->addVar('metaTitle', 'Administration - Gérer les listes de diffusion');
        
        return $this->response->render('admin/mailing', $params);
    }
}
