<?php
namespace Jeero\Theaters\Widgets;
use Jeero\Subscriptions\Subscription;

abstract class Widget {

	protected Widget $widget;

    public function _construct( Widget $widget ) {
        $this->widget = $widget;
    }

	public function register(): void {
        add_filter(
            'jeero/theaters/widgets/widget/render/' . $this->get_name(),
            [ $this, 'render' ],
            10,
            3
        );

        add_action(
            'jeero/template_element/enqueue/' . $this->get_name(),
            [ $this, 'enqueue' ],
            10,
            2
        );
    }

    abstract protected function render( Subscription $subscription, array $args = [] ): string;

}
