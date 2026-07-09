/**
 * WordPress dependencies
 */
import { __experimentalScrollable as Scrollable, Button, ExternalLink } from '@wordpress/components';
import { file } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Icon resource websites
 */
const iconResources = [
    {
        name: 'Heroicons',
        url: 'https://heroicons.com/',
        description: __('Beautiful hand-crafted SVG icons by Tailwind CSS', 'pandora-group')
    },
    {
        name: 'Google Fonts Icons',
        url: 'https://fonts.google.com/icons',
        description: __('Material icons and symbols', 'pandora-group')
    },
    {
        name: 'Remix Icon',
        url: 'https://remixicon.com/',
        description: __('Open-source icon library', 'pandora-group')
    },
    {
        name: 'Basicons',
        url: 'https://basicons.xyz/',
        description: __('Basic icons for your projects', 'pandora-group')
    }
];

export const Sidebar = ({ categories = [], category, setCategory, isCustomTab }) => {
    return (
        <div className="pandora-modal__sidebar">
            {isCustomTab ? (
                <>
                    <h4 className="icons-heading">{__('Resources', 'pandora-group')}</h4>
                    <p
                        style={{
                            fontSize: '12px',
                            lineHeight: '1.5',
                            color: '#757575',
                            marginBottom: '16px',
                            marginTop: '0'
                        }}
                    >
                        {__(
                            'Want to try a different icon? Maybe find one from these resources, Copy the SVG code and paste it in the editor.',
                            'pandora-group'
                        )}
                    </p>
                    <Scrollable className="pandora-modal__scrollable">
                        <div className="pandora-modal__sidebar-buttons">
                            {iconResources.map(resource => (
                                <ExternalLink href={resource.url} className="pandora-modal__sidebar-link">
                                    {resource.name}
                                </ExternalLink>
                            ))}
                        </div>
                    </Scrollable>
                </>
            ) : (
                <>
                    <h4 className="icons-heading">{__('Categories', 'pandora-group')}</h4>
                    <Scrollable className="pandora-modal__scrollable">
                        <div className="pandora-modal__sidebar-buttons">
                            {categories.map(cat => (
                                <Button
                                    key={cat.slug}
                                    icon={file}
                                    iconSize={20}
                                    className={`pandora-modal__sidebar-button ${category === cat.slug ? 'is-selected' : ''}`}
                                    onClick={() => setCategory(cat.slug)}
                                >
                                    {cat.name}
                                    <span
                                        style={{
                                            flex: '1',
                                            textAlign: 'right'
                                        }}
                                    >
                                        {cat.count || '0'}
                                    </span>
                                </Button>
                            ))}
                        </div>
                    </Scrollable>
                </>
            )}
        </div>
    );
};
