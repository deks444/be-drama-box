pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup Environment & Portable PHP') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env dengan kutip ganda untuk menangani spasi folder
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Download Static PHP yang benar-benar stabil (dari static-php-cli)
                        echo "Mengunduh PHP Portable..."
                        sh '''
                            mkdir -p local_bin
                            # Mengunduh PHP 8.2 static binary
                            curl -Lo php.tar.gz https://github.com/crazywhalecc/static-php-cli/releases/download/v1.6.4/php-8.2.16-cli-linux-x86_64.tar.gz
                            tar -xzf php.tar.gz -C local_bin/
                            chmod +x local_bin/php
                            ./local_bin/php -v
                        '''
                    }
                }
            }
        }

        stage('Install Composer') {
            steps {
                echo 'Installing Composer...'
                sh '''
                    # Download installer composer menggunakan PHP portable
                    curl -sS https://getcomposer.org/installer -o composer-setup.php
                    ./local_bin/php composer-setup.php
                    
                    # Jalankan install. Tambahkan --ignore-platform-reqs jika env Jenkins sangat terbatas
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Running Artisan Tasks...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Kita coba chmod, jika gagal karena permission kita abaikan (continue-on-error)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }

        stage('Optimization & Database') {
            steps {
                echo 'Finalizing...'
                sh '''
                    ./local_bin/php artisan migrate --force || echo "Migration failed, check DB connection"
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                '''
            }
        }
    }

    post {
        always {
            // Bersihkan file installer untuk menghemat ruang
            sh 'rm -f php.tar.gz composer-setup.php'
        }
        success {
            echo "Deployment DRAMA-BOX-AUTH Berhasil!"
        }
    }
}
