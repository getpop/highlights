<?php
namespace PoP\Highlights\FieldResolvers;

use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldResolvers\AbstractDBDataFieldResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\Posts\TypeResolvers\PostUnionTypeResolver;
use PoP\Highlights\TypeResolvers\HighlightTypeResolver;

class HighlightFieldResolver extends AbstractDBDataFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(HighlightTypeResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'title',
            'excerpt',
            'content',
            'highlightedpost',
            'highlightedpost-url',
            'highlightedpost',
        ];
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            'title' => SchemaDefinition::TYPE_STRING,
            'excerpt' => SchemaDefinition::TYPE_STRING,
            'content' => SchemaDefinition::TYPE_STRING,
            'highlightedpost' => SchemaDefinition::TYPE_ID,
            'highlightedpost-url' => SchemaDefinition::TYPE_URL,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'title' => $translationAPI->__('', ''),
            'excerpt' => $translationAPI->__('', ''),
            'content' => $translationAPI->__('', ''),
            'highlightedpost' => $translationAPI->__('', ''),
            'highlightedpost-url' => $translationAPI->__('', ''),
            'highlightedpost' => $translationAPI->__('', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function resolveValue(TypeResolverInterface $typeResolver, $resultItem, string $fieldName, array $fieldArgs = [], ?array $variables = null, ?array $expressions = null, array $options = [])
    {
        $cmspostsapi = \PoP\Posts\FunctionAPIFactory::getInstance();
        $highlight = $resultItem;
        switch ($fieldName) {

         // Override fields for Highlights
         // The Stance has no title, so return the excerpt instead.
         // Needed for when adding a comment on the Stance, where it will say: Add comment for...
            case 'title':
            case 'excerpt':
            case 'content':
                $value = $cmspostsapi->getBasicPostContent($highlight);
                if ($fieldName == 'title') {
                    return limitString($value, 100);
                } elseif ($fieldName == 'excerpt') {
                    return limitString($value, 300);
                }
                return $value;

            case 'highlightedpost':
                return \PoP\PostMeta\Utils::getPostMeta($typeResolver->getId($highlight), GD_METAKEY_POST_HIGHLIGHTEDPOST, true);

            case 'highlightedpost-url':
                return $cmspostsapi->getPermalink($typeResolver->resolveValue($highlight, 'highlightedpost'), $variables, $expressions, $options);
        }

        return parent::resolveValue($typeResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'highlightedpost':
                return PostUnionTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName, $fieldArgs);
    }
}
