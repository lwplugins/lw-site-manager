/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../../components/Callout';
import LoadError from '../../components/LoadError';
import Section from '../../components/Section';
import StatusBadge from '../../components/StatusBadge';
import {
	SkeletonRegion,
	SkeletonRows,
	SkeletonSection,
} from '../../components/skeleton';
import { errorMessage } from '../../data/api';

/**
 * Translated name of a skill source ("built-in" is ours; other plugins'
 * sources bring their own label).
 *
 * @param {Object} skill Skill.
 * @return {string} Label.
 */
const sourceLabel = ( skill ) =>
	skill.source === 'built-in'
		? __( 'Built-in', 'lw-site-manager' )
		: skill.sourceLabel || skill.source;

/**
 * The skills an agent can load: slug, source, description.
 *
 * @param {Object} props
 * @param {Object} props.skills useRemote() of the skill list.
 */
export default function SkillsTab( { skills } ) {
	if ( ! skills.data ) {
		return skills.error ? (
			<LoadError
				message={ errorMessage( skills.error ) }
				onRetry={ () => skills.reload() }
			/>
		) : (
			<SkeletonRegion className="lw-skel-tab">
				<SkeletonSection>
					<SkeletonRows count={ 3 } />
				</SkeletonSection>
			</SkeletonRegion>
		);
	}

	return (
		<Section
			title={ __( 'Skills', 'lw-site-manager' ) }
			badge={
				<StatusBadge status="idle">
					{ String( skills.data.length ) }
				</StatusBadge>
			}
			description={ __(
				'Step-by-step playbooks for common jobs. Agents find them in the discover-abilities result and load one with the skill-get ability.',
				'lw-site-manager'
			) }
		>
			{ skills.data.length ? (
				<ul className="lw-sm-skills">
					{ skills.data.map( ( skill ) => (
						<li key={ skill.key } className="lw-sm-skill">
							<div className="lw-sm-skill__head">
								<code>{ skill.slug }</code>
								<StatusBadge status="info">
									{ sourceLabel( skill ) }
								</StatusBadge>
								{ ! skill.agentic && (
									<StatusBadge status="idle">
										{ __(
											'Not listed for agents',
											'lw-site-manager'
										) }
									</StatusBadge>
								) }
							</div>
							{ skill.description && (
								<p className="lw-sm-skill__desc">
									{ skill.description }
								</p>
							) }
						</li>
					) ) }
				</ul>
			) : (
				<Callout>
					{ __( 'No skills are registered.', 'lw-site-manager' ) }
				</Callout>
			) }
			<Callout>
				{ __(
					'Other plugins can add their own skills through the lw_site_manager_skill_sources filter.',
					'lw-site-manager'
				) }
			</Callout>
		</Section>
	);
}
