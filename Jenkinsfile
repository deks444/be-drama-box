pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        stage('Checkout') {
            steps {
                // Mengambil kode dari repo GitHub
                checkout scm
            }
        }

        stage('Setup Environment & PHP') {
            steps {
                echo 'Setting up .env and Portable Runtime...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env (Menangani spasi folder dengan kutip ganda)
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Download PHP dengan Fallback Mechanism
                        sh '''
                            mkdir -p local_bin
                            echo "Mengunduh PHP..."
                            
                            # Coba download versi tar.gz (dengan flag -L dan -k untuk bypass SSL)
                            curl -Lk "https://github.com/crazywhalecc/static-php-cli/releases/download/2.8.0/spc-linux-x86_64.tar.gz" -o php.tar.gz
                            
                            if tar -xzf php.tar.gz -C local_bin/ 2>/dev/null; then
                                echo "Ekstrak berhasil."
                            else
                                echo "Ekstrak gagal. Mencoba download binary langsung (tanpa kompresi)..."
                                curl -Lk "https://github.com/crazywhalecc/static-php-cli/releases/download/2.8.0/spc-linux-x86_64.tar.gz" -o local_bin/php
                            fi
                            
                            chmod +x local_bin/php
                            ./local_bin/php -v
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                sh '''
                    # Download composer.phar langsung (lebih stabil daripada installer)
                    curl -Lk https://getcomposer.org/download/latest-stable/composer.phar -o composer.phar
                    
                    # Jalankan install dengan ignore-platform-reqs
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Finalize') {
            steps {
                echo 'Finalizing Laravel Setup...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Jalankan migrasi jika DB sudah siap
                    ./local_bin/php artisan migrate --force || echo "Migrasi gagal/lewati."
                    
                    # Optimasi Cache
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    
                    # Set permission (abaikan jika permission denied)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            echo 'Cleaning up temporary files...'
            sh 'rm -f php.tar.gz composer.phar'
        }
        success {
            echo "=========================================="
            echo " DEPLOYMENT DRAMA-BOX-AUTH BERHASIL!      "
            echo "=========================================="
        }
    }
}
