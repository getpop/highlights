<?php
namespace PoP\Highlights\FieldResolvers;

use PoP\ComponentModel\Utils;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldResolvers\AbstractDBDataFieldResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\LooseContracts\Facades\NameResolverFacade;
use PoP\Posts\TypeResolvers\PostTypeResolver;
use PoP\ComponentModel\Schema\TypeCastingHelpers;
use PoP\Highlights\TypeResolvers\HighlightTypeResolver;

class PostFieldResolver extends AbstractDBDataFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(
            PostTypeResolver::class,
        );
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'highlights',
            'has-highlights',
            'highlights-count',
        ];
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            'highlights' => TypeCastingHelpers::combineTypes(SchemaDefinition::TYPE_ARRAY, SchemaDefinition::TYPE_ID),
            'has-highlights' => SchemaDefinition::TYPE_BOOL,
            'highlights-count' => SchemaDefinition::TYPE_INT,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'highlights' => $translationAPI->__('', ''),
            'has-highlights' => $translationAPI->__('', ''),
            'highlights-count' => $translationAPI->__('', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function resolveValue(TypeResolverInterface $typeResolver, $resultItem, string $fieldName, array $fieldArgs = [], ?array $variables = null, ?array $expressions = null, array $options = [])
    {
        $post = $resultItem;
        $cmspostsapi = \PoP\Posts\FunctionAPIFactory::getInstance();
        switch ($fieldName) {
            case 'highlights':
                $query = array(
                    // 'fields' => 'ids',
                    'limit'/*'posts-per-page'*/ => -1, // Bring all the results
                    'meta-query' => [
                        [
                            'key' => \PoP\PostMeta\Utils::getMetaKey(GD_METAKEY_POST_HIGHLIGHTEDPOST),
                            'value' => $typeResolver->getId($post),
                        ],
                    ],
                    'post-types' => [POP_ADDHIGHLIGHTS_POSTTYPE_HIGHLIGHT],
                    'orderby' => NameResolverFacade::getInstance()->getName('popcms:dbcolumn:orderby:posts:date'),
                    'order' => 'ASC',
                );

                return $cmspostsapi->getPosts($query, ['return-type' => POP_RETURNTYPE_IDS]);

            case 'has-highlights':
                $referencedby = $typeResolver->resolveValue($resultItem, 'highlights', $variables, $expressions, $options);
                return !empty($referencedby);

            case 'highlights-count':
                $referencedby = $typeResolver->resolveValue($resultItem, 'highlights', $variables, $expressions, $options);
                return count($referencedby);
        }

        return parent::resolveValue($typeResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'highlights':
                return HighlightTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName, $fieldArgs);
    }
}
