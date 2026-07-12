import { registerBlockType } from '@wordpress/blocks';
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';
import metadata from './block.json';

const inlineIcon = (
    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24" fill="#1f1f1f">
        <path d="M120-160q-33 0-56.5-23.5T40-240v-480q0-33 23.5-56.5T120-800h80q33 0 56.5 23.5T280-720v480q0 33-23.5 56.5T200-160zm0-79h80v-482h-80zm320 79q-33 0-56.5-23.5T360-240v-480q0-33 23.5-56.5T440-800h400q33 0 56.5 23.5T920-720v480q0 33-23.5 56.5T840-160zm0-79h400v-482H440zm-240 0v-482zm240 0v-482z" />
    </svg>
);

registerBlockType(metadata.name, {
    icon: inlineIcon,
    /**
     * @see ./edit.js
     */
    edit: Edit,

    /**
     * @see ./save.js
     */
    save
});
