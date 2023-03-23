<?php
Class mysql
{

    public $query;
    public $fetchAll;
    public $result;
    public $response;
    protected $config;
    protected $driver;
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $dbname;
    protected $con;

    public function __construct( $config )
    {
        try
        {
            #array com dados do banco
            $this->config = $config;
            # Recupera os dados de conexao do config
            $this->dbname = $this->config['dbname'];
            $this->driver = $this->config['driver'];
            $this->host = $this->config['host'];
            $this->port = $this->config['port'];
            $this->user = $this->config['user'];
            $this->pass = $this->config['password'];
            # instancia e retorna objeto
            $this->con = @mysqli_connect( "$this->host", "$this->user", "$this->pass" );
            @mysqli_select_db( "$this->dbname" );
            if( !$this->con )
            {
                throw new Exception( "Falha na conex�o MySql com o banco [$this->dbname] em " . DATABASEDIR . "database.conf.php" );
            }
            else
            {
                return $this->con;
            }
        }
        catch( Exception $e )
        {
            echo $e->getMessage();
            exit;
        }
        return $this;
    }

    public function query( $query = '' )
    {
        try
        {
            if( $query == '' )
            {
                throw new Exception( 'mysql query: A query deve ser informada como par�metro do m�todo.' );
            }
            else
            {
                $this->query = $query;
                $this->result = @mysqli_query( $this->query );
                if(!$this->result)
                {
                    $this->response = "Erro " .@mysqli_errno()." => ". @mysqli_error();
                }
                else
                {
                    $this->response = "success";
                }                
            }
        }
        catch( Exception $e )
        {
            echo $e->getMessage();
            exit;
        }
        return $this;
    }

    public function fetchAll()
    {
        $this->fetchAll = "";
        while( $row = @mysqli_fetch_array( $this->result, mysqli_ASSOC ) )
        {
            $this->fetchAll[] = $row;
        }
        return $this->fetchAll;
    }

    public function rowCount()
    {
        return @mysqli_affected_rows();
    }

    public function limit( $limit, $offset )
    {
        return "LIMIT " . (int) $limit . "," . (int) $offset;
    }
}
/* end file */