<?php
if(!class_exists('Tr_Pagination_class')):

class Tr_Pagination_class{
    
    var $number = 30;
    var $offset = 0;
    var $total = 0;
    var $total_page = 0;
    
    function Tr_Pagination_class($limit=30)
    {
        $this->number = $limit;
        $this->total = 0;
    }
    
    public function get_paged()
    {
        $paged = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;
        $paged = max( 1, $paged );
        return $paged;
    }
    
    public function offset()
    {
        return ($this->get_paged() - 1) * $this->number;
    }
    
    public function number()
    {
        return $this->number;
    }
    
    public function setTotal($total)
    {
        
        $this->total = $total;
        if($this->number > 0)
        {
            $this->total_page = ceil( $this->total / $this->number );
        }
        
        if($this->total_page ==0)$this->total_page =1;
    }
    
    public function pagination($arg_remove=array(),$which='top' ) {
        
        if($this->number <=0)return '';
        
		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $this->total ), number_format_i18n( $this->total ) ) . '</span>';

		$current = $this->get_paged();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first','_wpnonce' ), $current_url );
        if(count($arg_remove)> 0)
        {
            $current_url = remove_query_arg( $arg_remove, $current_url );
        }

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $this->total_page )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $this->total_page ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $this->total_page, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $this->total_page, $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $this->total_page )
			$page_class = $this->total_page < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
}


endif;

