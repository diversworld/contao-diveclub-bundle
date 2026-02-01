<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class StudentExerciseListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'config.onload')]
    public function onLoad(DataContainer $dc): void
    {
        if (Input::get('key') === 'completeExercise' && Input::get('rid')) {
            $this->completeExercise((int)Input::get('rid'));
        }
    }

    public function completeExercise(int $id): void
    {
        // CSRF prüfen (Contao backend request token "rt")
        $container = System::getContainer();
        $tokenManager = $container->get('contao.csrf.token_manager');
        $tokenId = (string)$container->getParameter('contao.csrf_token_name');
        $rt = (string)Input::get('rt');

        if ($rt === '' || !$tokenManager->isTokenValid(new CsrfToken($tokenId, $rt))) {
            throw new AccessDeniedException('Invalid request token.');
        }

        $db = Database::getInstance();

        $db->prepare("UPDATE tl_dc_student_exercises SET status='ok', dateCompleted=?, tstamp=? WHERE id=?")
            ->execute(time(), time(), $id);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            Backend::redirect('contao');
        }

        $params = $request->query->all();
        unset($params['key'], $params['rid'], $params['rt']);

        $path = $request->getBaseUrl() . $request->getPathInfo();
        $url = $path . (empty($params) ? '' : '?' . http_build_query($params));

        Backend::redirect($url);
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'operations.complete.button')]
    public function showCompleteButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        if (($row['status'] ?? '') === 'ok') {
            return Image::getHtml(str_replace('.svg', '_1.svg', (string)$icon), $label, 'class="disabled"');
        }

        $rt = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            // Fallback: alte Variante
            $url = Backend::addToUrl('key=completeExercise&rid=' . (int)$row['id'] . '&rt=' . $rt);
        } else {
            // aktuelle Parameter übernehmen und dann gezielt setzen/überschreiben
            $params = $request->query->all();
            $params['key'] = 'completeExercise';
            $params['rid'] = (int)$row['id'];
            $params['rt'] = $rt;

            $path = $request->getBaseUrl() . $request->getPathInfo();
            $url = $path . '?' . http_build_query($params);
        }

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $url,
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml((string)$icon, $label)
        );
    }
}
