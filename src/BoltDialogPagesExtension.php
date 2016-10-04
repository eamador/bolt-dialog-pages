<?php

namespace Bolt\Extension\Eamador\BoltDialogPages;

use Silex\Application;
use Bolt\Extension\SimpleExtension;
use Silex\ControllerCollection;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Bolt\Asset\File\JavaScript;
use Bolt\Controller\Zone;

/**
 * BoltDialogPages extension class.
 *
 * @author Eduardo Amador <eamadorpaton@gmail.com>
 */
class BoltDialogPagesExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerAssets() {
        // the loading order is important in this case but it seems that the AssetTrait does not keep the same order always
        // temporary solution is to load the scripts on _list.twig
        // TODO: open an issue on github
//        return [
//          (new JavaScript('jquery.dataTables.min.js'))->setZone(Zone::BACKEND),
//          (new JavaScript('dataTables.bootstrap.min.js'))->setZone(Zone::BACKEND),
//          (new JavaScript('extension.js'))->setZone(Zone::BACKEND),
//        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
          'get_dialog_buttons' => 'getDialogButtons'
        ];
    }

    /**
     * {@inheritdoc}
     * You'll find the new menu option under "Extras".
     */
    protected function registerMenuEntries()
    {
        $adminMenuEntry = (new MenuEntry('dialog-pages-list', 'dialog-pages'))
          ->setLabel('Bolt-Dialog-Pages Admin')
          ->setIcon('fa:child')
          ->setPermission('settings');

        return [$adminMenuEntry];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendRoutes(ControllerCollection $collection)
    {
        $collection->match('/extend/dialog-pages', [$this, 'dialogPagesList']);
        $collection->match('/extend/dialog-pages/new', [$this, 'dialogPagesNew']);
        $collection->match('/extend/dialog-pages/delete', [$this, 'dialogPageDelete']);
        $collection->match('/extend/dialog-pages/edit', [$this, 'dialogPageEdit']);
    }

    /**
     * Return an array with the pages id that has already a dialog button configured
     * @return array $existingIds
     */
    protected function getExistingDialogPagesIds() {
        $config = $this->getConfig();
        $existingIds = [];
        if (isset($config['buttons']) && count($config['buttons'])) {
            foreach ($config['buttons'] as $button) {
                $existingIds[] = $button['page'];
            }
        }

        return $existingIds;
    }

    /**
     * @param $config
     * @return bool
     */
    protected function persistConfigFile($config) {
        try {
            $app = $this->getContainer();
            $dumper = new Dumper();
            $dumper->setIndentation(4);
            $yaml = $dumper->dump($config, 9999);
            $parser = new Parser();
            $parser->parse($yaml);

            $app['filesystem']->getFile('config://extensions/boltdialogpages.eamador.yml')->put($yaml);

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param null $id
     * @param array $whereNotInId
     * @return mixed
     */
    protected function getPagesAsArray($id = null, Array $whereNotInId = []) {
        $app = $this->getContainer();

        $repo = $app['storage']->getRepository('pages');
        $qb = $repo->createQueryBuilder();
        $qb->where('status="published"');

        if ($id) {
            $qb->where('id = :id')
              ->setParameter(':id', $id);
        } elseif ($whereNotInId) {
            $qb->where('id NOT IN (:ids)')
              ->setParameter(':ids', $whereNotInId, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        return $qb->execute()->fetchAll();
    }

    /**
     * @return _form.twig with $context
     */
    public function dialogPagesNew(Application $app, Request $request) {

        $pagesDropdown = $this->getPagesAsArray(null, $this->getExistingDialogPagesIds());
        if (!count($pagesDropdown)) {
            $app['logger.flash']->error(Trans::__('dialogpages.flash.nopages'));
            return new RedirectResponse('/bolt/extend/dialog-pages', 301);
        }

        $config = $this->getConfig();
        if ($request->get('page') && $request->get('button')) {
            if (!isset($config['buttons'])) {
                $config['buttons'] = [];
            }
            $page = $this->getPagesAsArray($request->get('page'));
            if ($page) {
                $config['buttons'][] = [
                  'title' => $request->get('button'),
                  'class' => $page[0]['slug'],
                  'target_id' => $page[0]['slug'] . '-modal',
                  'page' => (int)$request->get('page')
                ];
            }

            if ($result = $this->persistConfigFile($config)) {
                $app['logger.flash']->success(Trans::__('dialogpages.flash.saved'));
                return new RedirectResponse('/bolt/extend/dialog-pages', 301);
            } else {
                $app['logger.flash']->error(Trans::__('dialogpages.flash.error'));
                return new RedirectResponse($app['resources']->getUrl('currenturl'), 301);
            }
        }

        $context = [
          'pages' => $pagesDropdown
        ];

        return $this->renderTemplate('_form.twig', $context);
    }

    /**
     * Edit the button label for the received id
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dialogPageEdit(Application $app, Request $request) {
        if ($request->get('id') && $request->get('button')) {
            $config = $this->getConfig();

            foreach ($config['buttons'] as $key => $button) {
                if ($button['page'] === (int) $request->get('id')) {
                    $config['buttons'][$key]['title'] = $request->get('button');
                }
            }

            if ($result = $this->persistConfigFile($config)) {
                $app['logger.flash']->success(Trans::__('dialogpages.flash.updated'));
            }
            else {
                $app['logger.flash']->error(Trans::__('dialogpages.flash.updateError'));
            }
        } else if ($request->get('id')) {

            $config = $this->getConfig();

            $buttonToEdit = [];
            foreach ($config['buttons'] as $key => $button) {
                if ($button['page'] === (int) $request->get('id')) {
                    $buttonToEdit = $config['buttons'][$key];
                    $page = $this->getPagesAsArray($request->get('id'));
                    $buttonToEdit['pageTitle'] = $page[0]['title'];
                }
            }

            return $this->renderTemplate('_edit.twig', ['edit' => $buttonToEdit]);

        } else {
            $app['logger.flash']->error(Trans::__('dialogpages.flash.updated'));
        }

        return new RedirectResponse('/bolt/extend/dialog-pages', 301);
    }

    /**
     * Delete the received entry from the config.yml
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dialogPageDelete(Application $app, Request $request) {
        if ($request->get('id')) {
            $config = $this->getConfig();

            foreach ($config['buttons'] as $key => $button) {
                if ($button['page'] === (int)$request->get('id')) {
                    unset($config['buttons'][$key]);
                }
            }

            if ($result = $this->persistConfigFile($config)) {
                $app['logger.flash']->success(Trans::__('dialogpages.flash.deleted'));
            } else {
                $app['logger.flash']->error(Trans::__('dialogpages.flash.deleteError'));
            }

        } else {
            $app['logger.flash']->error(Trans::__('dialogpages.flash.deleted'));
        }

        return new RedirectResponse('/bolt/extend/dialog-pages', 301);
    }

    /**
     * @return string
     */
    public function dialogPagesList() {
        $app = $this->getContainer();
        $config = $this->getConfig();
        $buttons = [];
        if (isset($config['buttons']) && count($config['buttons'])) {
            foreach ($config['buttons'] as $key => $button) {
                $page = $this->getPagesAsArray($button['page']);
                if ($page) {
                    $buttons[] = [
                        'button' => [
                            'title' => $button['title']
                        ],
                        'page' => [
                          'id' => $button['page'],
                          'title' => $page[0]['title']
                        ],
                        'delete_url' => $app['resources']->getUrl('currenturl').'/delete?id='.$button['page'],
                        'edit_url' => $app['resources']->getUrl('currenturl').'/edit?id='.$button['page']
                    ];
                }
            }
        }

        $context = [
          'new_link' => $app['resources']->getUrl('currenturl').'/new',
          'buttons' => $buttons
        ];

        return $this->renderTemplate('_list.twig', $context);
    }

    /**
     * The callback function when {{ get_dialog_buttons() }} is used in a template.
     *
     * @return string
     */
    public function getDialogButtons()
    {
        $lang = $_SERVER['REDIRECT_URL'];
        $config = $this->getConfig();

        if (isset($config['buttons']) && count($config['buttons'])) {
            foreach ($config['buttons'] as $key => $button) {
                if ($page = $this->getPagesAsArray($button['page'])) {
                    $language = str_replace('/', '', $lang);
                    if (isset($page[0][$language.'data']) && count(json_decode($page[0][$language.'data'], true))) {
                        $translatedPage = json_decode($page[0][$language.'data']);
                        $config['buttons'][$key]['page'] = $translatedPage->body;
                    } else {
                        $config['buttons'][$key]['page'] = $page[0]['body'];
                    }
                }
            }
        }

        $context = [
          'buttons' => isset($config['buttons']) ? $config['buttons'] : '',
          'intro' => isset($config['intro']) ? $config['intro'] : '',
        ];

        return $this->renderTemplate('_dialog_menu.twig', $context);
    }

}
