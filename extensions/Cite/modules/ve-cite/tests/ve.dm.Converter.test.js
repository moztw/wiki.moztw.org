/*!
 * VisualEditor DataModel Cite-specific Converter tests.
 *
 * @copyright 2011-2017 Cite VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.dm.Converter (Cite)', ve.test.utils.mwEnvironment );

QUnit.test( 'getModelFromDom', function ( assert ) {
	var msg, caseItem,
		cases = ve.dm.citeExample.domToDataCases;

	QUnit.expect( ve.test.utils.countGetModelFromDomTests( cases ) );

	for ( msg in cases ) {
		caseItem = ve.copy( cases[ msg ] );
		if ( caseItem.mwConfig ) {
			mw.config.set( caseItem.mwConfig );
		}

		ve.test.utils.runGetModelFromDomTest( assert, caseItem, msg );
	}
} );

QUnit.test( 'getDomFromModel', function ( assert ) {
	var msg, caseItem,
		cases = ve.dm.citeExample.domToDataCases;

	QUnit.expect( 3 * Object.keys( cases ).length );

	for ( msg in cases ) {
		caseItem = ve.copy( cases[ msg ] );
		if ( caseItem.mwConfig ) {
			mw.config.set( caseItem.mwConfig );
		}

		ve.test.utils.runGetDomFromModelTest( assert, caseItem, msg );
	}
} );
