/**
 * Internal dependencies
 */
import LoadError from '../../components/LoadError';
import { errorMessage } from '../../data/api';
import ConnectionSection from './ConnectionSection';
import McpSkeleton from './McpSkeleton';
import McpStateBar from './McpStateBar';
import Warnings from './Warnings';

/**
 * MCP server: state bar with the switch, what needs attention, and how to
 * connect a client.
 *
 * @param {Object} props
 * @param {Object} props.mcp    useRemote() of the MCP state.
 * @param {Object} props.toggle useMcpToggle().
 */
export default function McpTab( { mcp, toggle } ) {
	if ( ! mcp.data ) {
		return mcp.error ? (
			<LoadError
				message={ errorMessage( mcp.error ) }
				onRetry={ () => mcp.reload() }
			/>
		) : (
			<McpSkeleton />
		);
	}

	return (
		<>
			<McpStateBar state={ mcp.data } toggle={ toggle } />
			<Warnings warnings={ mcp.data.warnings } />
			<ConnectionSection state={ mcp.data } />
		</>
	);
}
