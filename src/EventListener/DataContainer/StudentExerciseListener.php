<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

class StudentExerciseListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'config.onload')]
    public function onLoad(DataContainer $dc): void
    {
        if (Input::get('key') === 'completeExercise' && Input::get('id')) {
            $this->completeExercise((int)Input::get('id'));
        }
    }

    public function completeExercise(int $id): void
    {
        $db = Database::getInstance();

        $db->prepare("UPDATE tl_dc_student_exercises SET status='ok', dateCompleted=? WHERE id=?")
            ->execute(time(), $id);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $referer = $request->headers->get('referer');
            if ($referer) {
                // URL säubern
                $url = preg_replace('/[&?]key=completeExercise/', '', $referer);
                $url = preg_replace('/[&?]id=\d+/', '', $url);
                header('Location: ' . $url);
                exit;
            }
        }
    }

    #[AsCallback(table: 'tl_dc_student_exercises', target: 'operations.complete.button')]
    public function showCompleteButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        if ($row['status'] === 'ok') {
            return Image::getHtml(str_replace('.svg', '_1.svg', (string)$icon), $label, 'class="disabled"');
        }

        return sprintf('<a href="%s" title="%s"%s>%s</a> ',
            System::getContainer()->get('contao.routing.backend_router')->generate('contao_backend', [
                'do' => Input::get('do'),
                'table' => 'tl_dc_student_exercises',
                'key' => 'completeExercise',
                'id' => $row['id'],
                'rt' => System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()
            ]),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml((string)$icon, $label)
        );
    }
}
