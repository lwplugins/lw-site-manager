/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Icon,
	chevronDown,
	chevronRight,
	external,
	help,
} from '@wordpress/icons';
import { Badge } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import SiteManagerMark from '../components/SiteManagerMark';
import { DOCS_URL, VERSION } from '../data/boot';

/**
 * Full-height sidebar: plugin header, section links, docs link. On mobile the
 * list collapses behind a "current section" toggle.
 *
 * @param {Object} props
 * @param {Array}  props.tabs    Tab registry.
 * @param {string} props.current Active tab id.
 * @param {Object} props.meta    Optional node per tab id, after the label.
 * @param {string} props.docsUrl Documentation URL (server meta wins).
 */
export default function SideNav( { tabs, current, meta = {}, docsUrl } ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const navId = useInstanceId( SideNav, 'lw-admin-sidenav' );
	const active = tabs.find( ( tab ) => tab.id === current );

	useEffect( () => setIsOpen( false ), [ current ] );

	return (
		<aside className={ `lw-admin-sidebar ${ isOpen ? 'is-open' : '' }` }>
			<div className="lw-admin-sidebar__head">
				<a
					className="lw-admin-sidebar__home"
					href={ `#${ tabs[ 0 ].id }` }
					aria-label={ __(
						'LW Site Manager home',
						'lw-site-manager'
					) }
				>
					<SiteManagerMark />
					<strong>LW Site Manager</strong>
				</a>
				<Badge intent="informational">{ `v${ VERSION }` }</Badge>
			</div>
			<button
				type="button"
				className="lw-admin-sidebar__toggle"
				aria-expanded={ isOpen }
				aria-controls={ navId }
				onClick={ () => setIsOpen( ! isOpen ) }
			>
				<Icon icon={ active.icon } size={ 20 } />
				<span>{ active.label }</span>
				{ meta[ active.id ] }
				<Icon icon={ chevronDown } size={ 20 } />
			</button>
			<nav
				id={ navId }
				className="lw-admin-sidenav"
				aria-label={ __(
					'LW Site Manager sections',
					'lw-site-manager'
				) }
			>
				<ul>
					{ tabs.map( ( tab ) => {
						const isCurrent = tab.id === current;
						return (
							<li key={ tab.id }>
								<a
									href={ `#${ tab.id }` }
									className="lw-admin-sidenav__item"
									aria-current={
										isCurrent ? 'page' : undefined
									}
								>
									<Icon icon={ tab.icon } size={ 20 } />
									<span className="lw-admin-sidenav__label">
										{ tab.label }
									</span>
									{ meta[ tab.id ] && (
										<span className="lw-admin-sidenav__meta">
											{ meta[ tab.id ] }
										</span>
									) }
									{ isCurrent && (
										<Icon
											icon={ chevronRight }
											size={ 18 }
										/>
									) }
								</a>
							</li>
						);
					} ) }
				</ul>
			</nav>
			<div className="lw-admin-sidebar__foot">
				<a
					className="lw-admin-sidenav__item"
					href={ docsUrl || DOCS_URL }
					target="_blank"
					rel="noopener noreferrer"
				>
					<Icon icon={ help } size={ 20 } />
					<span className="lw-admin-sidenav__label">
						{ __( 'Documentation', 'lw-site-manager' ) }
					</span>
					<Icon icon={ external } size={ 16 } />
					<span className="screen-reader-text">
						{ __( '(opens in a new tab)', 'lw-site-manager' ) }
					</span>
				</a>
			</div>
		</aside>
	);
}
