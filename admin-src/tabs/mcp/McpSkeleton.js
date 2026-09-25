/**
 * WordPress dependencies
 */
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	SkeletonBlock,
	SkeletonRegion,
	SkeletonSection,
	SkeletonText,
} from '../../components/skeleton';

/**
 * Placeholder of the MCP tab: the state bar (icon, two lines, switch, facts)
 * and the connection card, in their real sizes.
 */
export default function McpSkeleton() {
	return (
		<SkeletonRegion className="lw-skel-tab">
			<Card className="lw-admin-section lw-sm-state">
				<div className="lw-sm-state__bar">
					<SkeletonBlock width={ 48 } height={ 48 } />
					<span className="lw-sm-state__text">
						<SkeletonText width="40%" size="lg" />
						<SkeletonText width="70%" size="sm" />
					</span>
					<SkeletonBlock width={ 64 } height={ 20 } round />
				</div>
				<div className="lw-sm-state__facts">
					{ [ 0, 1, 2 ].map( ( index ) => (
						<div key={ index } className="lw-sm-state__fact">
							<SkeletonText width="40%" size="sm" />
							<SkeletonText width="70%" />
						</div>
					) ) }
				</div>
			</Card>
			<SkeletonSection>
				<SkeletonBlock height={ 34 } />
				<SkeletonText lines={ 3 } />
				<SkeletonBlock height={ 180 } />
			</SkeletonSection>
		</SkeletonRegion>
	);
}
