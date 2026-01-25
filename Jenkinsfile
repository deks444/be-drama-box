pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout') {
            steps {
                // Menarik source code dari repositori Git
                checkout scm
            }
        }

        stage('Setup PHP 8.2.27 & Environment') {
            steps {
                echo 'Mengunduh PHP 8.2.27 dari Mirror Resmi...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup file .env (Handle path dengan spasi)
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Download PHP 8.2.27 Static Binary
                        sh '''
                            mkdir -p local_bin
                            
                            # Link mirror resmi untuk PHP 8.2.27 CLI Linux x86_64
                            URL="https://dl.static-php.dev/static-php-cli/common/php-8.2.27-cli-linux-x86_64.tar.gz"
                            
                            curl -Lk "$URL" -o php.tar.gz
                            
                            # Ekstrak dan standarisasi nama binary ke 'php'
                            if tar -xzf php.tar.gz -C local_bin/; then
                                find local_bin -name "php*" -type f -exec mv {} local_bin/php \\;
                                chmod +x local_bin/php
                                echo "PHP Berhasil Terpasang:"
                                ./local_bin/php -v
                            else
                                echo "ERROR: File tidak ditemukan atau link 404. Periksa dl.static-php.dev"
                                exit 1
                            fi
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                sh '''
                    # Download composer.phar versi stabil
                    curl -Lk https://getcomposer.org/composer.phar -o composer.phar
                    
                    # Jalankan install (mengabaikan requirement sistem Jenkins yang minimal)
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Finalizing Laravel Setup...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Migrasi database (lanjutkan meskipun gagal)
                    ./local_bin/php artisan migrate --force || echo "Migrasi dilewati."
                    
                    # Optimasi Cache
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    ./local_bin/php artisan view:cache
                    
                    # Izin folder (Opsional)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            echo 'Pembersihan file sementara...'
            sh 'rm -f php.tar.gz composer.phar'
        }
        success {
            echo '==================================================='
            echo ' DEPLOY DRAMA-BOX-AUTH (PHP 8.2.27) BERHASIL!      '
            echo '==================================================='
        }
        failure {
            echo 'Build Gagal. Periksa koneksi internet atau kredensial Jenkins.'
        }
    }
}
