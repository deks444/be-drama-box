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
                // Mengambil kode dari GitHub
                checkout scm
            }
        }

        stage('Setup Runtime & .env') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env (Handling folder dengan spasi)
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Cari link download secara otomatis melalui GitHub API
                        // Ini akan selalu mengambil versi terbaru agar tidak kena 404
                        sh '''
                            mkdir -p local_bin
                            echo "Mencari link PHP terbaru dari GitHub API..."
                            
                            LATEST_URL=$(curl -sk https://api.github.com/repos/crazywhalecc/static-php-cli/releases/latest | grep "browser_download_url" | grep "php-8.2" | grep "cli-linux-x86_64.tar.gz" | cut -d '"' -f 4 | head -n 1)

                            if [ -z "$LATEST_URL" ]; then
                                echo "API Gagal, mencoba link mirror stabil..."
                                LATEST_URL="https://dl.static-php.dev/static-php-cli/common/php-8.2-cli-linux-x86_64.tar.gz"
                            fi

                            echo "Mendownload dari: $LATEST_URL"
                            curl -Lk "$LATEST_URL" -o php.tar.gz
                            
                            # Ekstrak dan verifikasi
                            tar -xzf php.tar.gz -C local_bin/
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
                    # Selalu gunakan composer.phar versi stabil terbaru
                    curl -Lk https://getcomposer.org/composer-stable.phar -o composer.phar
                    
                    # Install dependencies (Ignore platform reqs agar tidak error library sistem)
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Finalizing Laravel...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Jalankan migrasi database
                    ./local_bin/php artisan migrate --force || echo "Migrasi gagal atau database belum siap."
                    
                    # Optimasi Cache
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    
                    # Permission (Abaikan jika permission denied)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            echo 'Cleaning up...'
            sh 'rm -f php.tar.gz composer.phar'
        }
        success {
            echo '============================================='
            echo ' DEPLOYMENT DRAMA-BOX-AUTH BERHASIL!         '
            echo '============================================='
        }
    }
}
