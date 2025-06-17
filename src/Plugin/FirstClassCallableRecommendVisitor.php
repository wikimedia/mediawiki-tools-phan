<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use ast;
use ast\Node;
use Exception;
use Phan\AST\ASTReverter;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\Language\Element\Func;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\UnionType;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;

class FirstClassCallableRecommendVisitor extends PluginAwarePostAnalysisVisitor {

	/**
	 * @param Node $node a node of type AST_CALL
	 * @inheritDoc
	 */
	public function visitCall( Node $node ): void {
		$args = $node->children['args']->children;
		$expression = $node->children['expr'];
		try {
			$function_list_generator = ( new ContextNode(
				$this->code_base,
				$this->context,
				$expression
			) )->getFunctionFromNode();

			foreach ( $function_list_generator as $function ) {
				// @phan-suppress-next-line PhanPartialTypeMismatchArgument
				$this->checkCall( $function, $args, $node );
			}
		} catch ( Exception $_ ) {
		}
	}

	/**
	 * @param Node $node a node of type AST_NULLSAFE_METHOD_CALL
	 * @inheritDoc
	 */
	public function visitNullsafeMethodCall( Node $node ): void {
		$this->visitMethodCall( $node );
	}

	/**
	 * @param Node $node a node of type AST_METHOD_CALL
	 * @inheritDoc
	 */
	public function visitMethodCall( Node $node ): void {
		$args = $node->children['args']->children;
		$method_name = $node->children['method'];
		if ( !\is_string( $method_name ) ) {
			return;
		}
		try {
			$method = ( new ContextNode(
				$this->code_base,
				$this->context,
				$node
			) )->getMethod( $method_name, false, true );

			// @phan-suppress-next-line PhanPartialTypeMismatchArgument
			$this->checkCall( $method, $args, $node );
		} catch ( Exception $_ ) {
		}
	}

	/**
	 * @param Node $node a node of type AST_STATIC_CALL
	 * @inheritDoc
	 */
	public function visitStaticCall( Node $node ): void {
		$args = $node->children['args']->children;
		$method_name = $node->children['method'];
		if ( !\is_string( $method_name ) ) {
			return;
		}
		try {
			$method = ( new ContextNode(
				$this->code_base,
				$this->context,
				$node
			) )->getMethod( $method_name, true, true );

			// @phan-suppress-next-line PhanPartialTypeMismatchArgument
			$this->checkCall( $method, $args, $node );
		} catch ( Exception $_ ) {
		}
	}

	/**
	 * @param FunctionInterface $function
	 * @param list<Node|string|int|float> $args
	 * @param Node $node
	 */
	private function checkCall( FunctionInterface $function, array $args, Node $node ): void {
		foreach ( $args as $i => $arg ) {
			// Handle named and variadic params
			if ( $arg instanceof Node && $arg->kind === ast\AST_NAMED_ARG ) {
				foreach ( $function->getParameterList() as $idx => $param ) {
					if ( $param->getName() === $arg->children['name'] ) {
						break;
					}
					$param = null;
				}
				$arg = $arg->children['expr'];
			} else {
				$param = $function->getParameterForCaller( $i );
			}
			if ( !$param ) {
				continue;
			}

			$paramType = $param->getUnionType();

			$suggestedCode = $this->checkValueShouldBeChanged( $paramType, $arg );
			if ( $suggestedCode ) {
				$isInternalFunc = $this->isCallableMaybeInternalFunc( $arg );

				$this->emitPluginIssue(
					$this->code_base,
					$this->context,
					$isInternalFunc ? FirstClassCallableRecommendPlugin::INTERNALFUNC_ISSUE_TYPE :
						FirstClassCallableRecommendPlugin::OTHER_ISSUE_TYPE,
					'Use first-class callable `{SUGGESTION}` instead of callable {TYPE} `{CODE}` ' .
						'(passed as argument {PARAMETER} to {FUNCTION})',
					[
						$suggestedCode,
						is_string( $arg ) ? 'string' : 'array',
						ASTReverter::toShortString( $arg ),
						/*$param->getShortRepresentationForIssue()*/ '$' . $param->getName(),
						$function->getRepresentationForIssue(),
					]
				);
			}
		}
	}

	/**
	 * @param Node $node
	 * @inheritDoc
	 */
	public function visitAssign( Node $node ): void {
		$var = $node->children['var'];
		if ( !$var instanceof Node ) {
			return;
		}
		if ( $var->kind !== ast\AST_STATIC_PROP && $var->kind !== ast\AST_PROP ) {
			return;
		}
		try {
			$prop = ( new ContextNode(
				$this->code_base,
				$this->context,
				$var
			) )->getProperty( $var->kind === ast\AST_STATIC_PROP, true );

			$arg = $node->children['expr'];

			// $prop->getUnionType() includes types deduced from analysis of assignments,
			// so it will include 'string' or 'array' if a callable string/array is ever assigned,
			// so it's useless for us.
			// $prop->getPHPDocUnionType() is correct because 'callable'
			// cannot be used as a class property type declaration.
			// https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.base.function
			$propType = $prop->getPHPDocUnionType();

			$suggestedCode = $this->checkValueShouldBeChanged( $propType, $arg );
			if ( $suggestedCode ) {
				$isInternalFunc = $this->isCallableMaybeInternalFunc( $arg );

				$this->emitPluginIssue(
					$this->code_base,
					$this->context,
					$isInternalFunc ? FirstClassCallableRecommendPlugin::INTERNALFUNC_ISSUE_TYPE :
						FirstClassCallableRecommendPlugin::OTHER_ISSUE_TYPE,
					'Use first-class callable `{SUGGESTION}` instead of callable {TYPE} `{CODE}` ' .
						'(assigned to property {PROPERTY})',
					[
						$suggestedCode,
						is_string( $arg ) ? 'string' : 'array',
						ASTReverter::toShortString( $arg ),
						$prop->getRepresentationForIssue(),
					]
				);
			}
		} catch ( Exception $_ ) {
		}
	}

	/**
	 * @param Node|string|int|float $value
	 * @return bool
	 */
	private function isCallableMaybeInternalFunc( $value ): bool {
		$functionLikes = UnionTypeVisitor::getFunctionLikesFromCallableNode(
			$this->code_base, $this->context, $value, false
		);
		foreach ( $functionLikes as $f ) {
			if ( $f->isPHPInternal() && $f instanceof Func ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param UnionType $targetType
	 * @param Node|string|int|float $value
	 * @return ?string
	 */
	private function checkValueShouldBeChanged( $targetType, $value ): ?string {
		// If the target is annotated as 'callable' or other callable type (optionally null),
		// and the value given is a literal string or array,
		// and Phan thinks it is really a valid callable string/array in this context,
		// then recommend that the first-class callable syntax is used.
		if (
			(
				is_string( $value ) ||
				( $value instanceof Node && $value->kind === ast\AST_ARRAY )
			) &&
			!$targetType->isEmpty() &&
			(
				$targetType->isExclusivelyCallable( $this->code_base ) ||
				$targetType->nonNullableClone()->isExclusivelyCallable( $this->code_base )
			) &&
			UnionTypeVisitor::unionTypeFromNode( $this->code_base, $this->context, $value )
				->canCastToUnionType( $targetType, $this->code_base )
		) {
			// Try to suggest the syntax to use
			if ( is_string( $value ) ) {
				// call_user_func( 'func' ); → call_user_func( func( ... ) );
				$suggestedCode = "{$value}( ... )";
			} else {
				$receiver = $value->children[0]->children['value'] ?? null;
				if ( !is_string( $receiver ) && !$receiver instanceof Node ) {
					// Too few array elements, or some other scalar type
					return null;
				}
				$method = $value->children[1]->children['value'] ?? null;
				if ( !is_string( $method ) && !$method instanceof Node ) {
					// Too few array elements, or some other scalar type
					return null;
				}
				if (
					$value->children[0]->flags === ast\flags\ARRAY_ELEM_REF ||
					$value->children[1]->flags === ast\flags\ARRAY_ELEM_REF
				) {
					// One of the elements of the callable array is a reference, this can't use the new syntax
					return null;
				}

				$methodRepr = is_string( $method ) ? $method : ASTReverter::toShortString( $method );
				if ( is_string( $receiver ) ) {
					// call_user_func( [ 'Cls', 'func' ] ); → call_user_func( Cls::func( ... ) );
					$suggestedCode = "{$receiver}::{$methodRepr}( ... )";

				} elseif ( $receiver->kind === ast\AST_CLASS_NAME ) {
					// call_user_func( [ Cls::class, 'func' ] ); → call_user_func( Cls::func( ... ) );
					// call_user_func( [ $x::class, 'func' ] ); → call_user_func( $x::func( ... ) );
					$receiverRepr = ASTReverter::toShortString( $receiver->children['class'] );
					$suggestedCode = "{$receiverRepr}::{$methodRepr}( ... )";

				} elseif ( $receiver->kind === ast\AST_MAGIC_CONST && $receiver->flags === ast\flags\MAGIC_CLASS ) {
					// call_user_func( [ __CLASS__, 'func' ] ); → call_user_func( self::func( ... ) );
					$suggestedCode = "self::{$methodRepr}( ... )";

				} else {
					// call_user_func( [ $obj, 'func' ] ); → call_user_func( $obj->func( ... ) );
					// call_user_func( [ $str, 'func' ] ); → call_user_func( $str::func( ... ) );
					// call_user_func( [ getFoo(), 'func' ] ); → ???

					// Ask Phan to determine whether the expression is a string or an object
					// to decide whether this is a static or instance method call
					$varType = UnionTypeVisitor::unionTypeFromNode( $this->code_base, $this->context, $receiver );
					if ( $varType->isScalar() ) {
						$call = '::';
					} elseif ( $varType->isObject() ) {
						$call = '->';
					} else {
						// We don't know how to fix this, don't report an error at all
						return null;
					}

					$receiverRepr = ASTReverter::toShortString( $receiver );
					$suggestedCode = "{$receiverRepr}{$call}{$methodRepr}( ... )";
				}
			}
			return $suggestedCode;
		}
		return null;
	}
}
