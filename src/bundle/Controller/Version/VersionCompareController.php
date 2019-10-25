<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUiBundle\Controller\Version;

use eZ\Publish\API\Repository\ContentComparisonService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use EzSystems\EzPlatformAdminUi\Util\FieldDefinitionGroupsUtil;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

final class VersionCompareController extends Controller
{
    /** @var \eZ\Publish\API\Repository\ContentComparisonService */
    private $compareService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \EzSystems\EzPlatformAdminUi\Util\FieldDefinitionGroupsUtil */
    private $fieldDefinitionGroupsUtil;

    public function __construct(
        ContentComparisonService $contentComparisonService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        FieldDefinitionGroupsUtil $fieldDefinitionGroupsUtil
    ) {
        $this->compareService = $contentComparisonService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->fieldDefinitionGroupsUtil = $fieldDefinitionGroupsUtil;
    }

    public function sideBySideCompareAction(
        Request $request,
        ContentInfo $contentInfo,
        int $versionNoA,
        int $versionNoB = null,
        string $languageCode = null
    ) {

        $contentA = $this->contentService->loadContent($contentInfo->id, $languageCode ?? [$languageCode], $versionNoA);
        $contentType = $this->contentTypeService->loadContentType($contentA->contentInfo->contentTypeId);
        $contentAfieldDefinitionsByGroup = $this->fieldDefinitionGroupsUtil->groupFieldDefinitions($contentType->getFieldDefinitions());

        $contentB = null;
        if ($versionNoB) {
            $contentB = $this->contentService->loadContent($contentInfo->id, $languageCode ?? [$languageCode], $versionNoB);
        }

        return $this->render(
            '@admin/content/comparison/side_by_side.html.twig',
                [
                    'content_a' => $contentA,
                    'content_b' => $contentB,
                    'field_definitions_by_group' => $contentAfieldDefinitionsByGroup,
                ]
        );
    }

    public function compareAction(
        Request $request,
        ContentInfo $contentInfo,
        int $versionNoA,
        int $versionNoB,
        string $languageCode = null
    ) {

        $versionInfoA = $this->contentService->loadVersionInfo($contentInfo, $versionNoA);
        $versionInfoB = $this->contentService->loadVersionInfo($contentInfo, $versionNoB);

        $versionDiff = $this->compareService->compareVersions(
            $versionInfoA,
            $versionInfoB,
            $languageCode
        );

        $contentA = $this->contentService->loadContentByVersionInfo($versionInfoA, $languageCode ?? [$languageCode]);

        return $this->render(
            '@admin/content/comparison/single.html.twig',
            [
                'version_diff' => $versionDiff,
                'content_a' => $contentA,
            ]
        );
    }
}
