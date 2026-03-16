<?php

declare(strict_types=1);

/**
 * Date: 31.08.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\GraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/graphql/explorer', name: 'youshido_graphql_explorer')]
class GraphQLExplorerController extends AbstractController
{
    #[Route('', name: 'youshido_graphql_explorer_index')]
    public function explorerAction(): Response
    {
        $response = $this->render('@GraphQLBundle/Feature/explorer.html.twig', [
            'graphQLUrl' => $this->generateUrl('youshido_graphql_graphql_default'),
            'tokenHeader' => 'access-token'
        ]);

        $date = \DateTime::createFromFormat('U', (string) strtotime('tomorrow'), new \DateTimeZone('UTC'));
        if ($date instanceof \DateTime) {
            $response->setExpires($date);
            $response->setPublic();
        }

        return $response;
    }
}
