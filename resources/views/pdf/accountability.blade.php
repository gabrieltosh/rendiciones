<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Asiento Contable</title>
    <style>
      * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
        }
        .table-border-md,
        .table-border-md td,
        .table-border-md th {
            border: 0.2px solid black;
            border-collapse: collapse;
            height: 17px !important;
        }
    </style>
</head>
<body>
    <table width="100%">
        <tr>
            <td align="left" style="width: 15%">
                <img src="{{ public_path('images/novanexa.jpg') }}" width="80">
            </td>
            <td align="left" style="width:55%">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>NOVANEXA SRL</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>La Paz - Bolivia</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>
                                NIT: 1231241221
                            </strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="font-size: 12px;width: 15%;padding:0px;margin:0px" align="right">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>Origen:</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>Número Origen:</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>Fecha:</strong>
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            <strong>T/C:</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="font-size: 12px;width: 15%;padding:0px;margin:0px" align="left">
                <table>
                    <tr style="font-size: 11px;">
                        <td>
                            Rendiciones
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            {{$accountability->id}}
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            {{Carbon\Carbon::parse($accountability->end_date)->format('d-m-Y')}}
                        </td>
                    </tr>
                    <tr style="font-size: 11px;">
                        <td>
                            {{$exchange}}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <tr>
            <td align="center" style="width: 100%; text-align: center;font-size: 15px;">
                <strong>
                    COMPROBANTE UNICO DE CONTABILIDAD
                </strong>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" class="table-border-md">
        <tr>
            <td align="left" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>
                    Cuenta
                </strong>
            </td>
            <td align="left" style="width: 40%; font-size: 10px;padding: 5px;">
                <strong>
                    Detalle
                </strong>
            </td>
            <td align="right" style="width: 15%; font-size: 10px;padding: 5px;">
                <strong>
                    Debe BS
                </strong>
            </td>
            <td align="right" style="width: 15%; font-size: 10px;padding: 5px;">
                <strong>
                    Haber BS
                </strong>
            </td>
            <td align="right" style="width: 15%; font-size: 10px;padding: 5px;">
                <strong>
                    Debe USD
                </strong>
            </td>
            <td align="right" style="width: 15%; font-size: 10px;padding: 5px;">
                <strong>
                    Haber USD
                </strong>
            </td>
        </tr>
        @php
            $total_debit=0;
            $total_credit=0;
            $total_debit_usd=0;
            $total_credit_usd=0;
        @endphp
        @foreach ($documents as $document)
        @php
            $total_debit+=$document['Debit'];
            $total_credit+=$document['Credit'];
            $total_debit_usd+=$document['Debit']*$exchange;
            $total_credit_usd+=$document['Credit']*$exchange;
        @endphp
        <tr>
            <td align="left" style="width: 20%; font-size: 10px; padding: 5px;">
                {{$document['AccountCode']}}
            </td>
            <td align="left" style="width: 20%; font-size: 10px; padding: 5px;">
                {{$document['AccountName']}}
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                {{number_format($document['Debit'],2) }}
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                {{number_format($document['Credit'],2) }}
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                {{number_format($document['Debit']*$exchange,2) }}
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                {{number_format($document['Credit']*$exchange,2) }}
            </td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2" align="center" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>Totales</strong>
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>
                    {{number_format($total_debit,2)}}
                </strong>
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>
                    {{number_format($total_credit,2)}}
                </strong>
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>
                    {{number_format($total_debit_usd,2)}}
                </strong>
            </td>
            <td align="right" style="width: 20%; font-size: 10px; padding: 5px;">
                <strong>
                    {{number_format($total_credit_usd,2)}}
                </strong>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <tr>
            <td style="font-size: 11px; ">
                <strong>
                    Glosa Contable
                </strong>
            </td>
        </tr>
        <tr style="font-size: 11px;">
            <td>{{$accountability->description}}</td>
        </tr>
    </table>
</body>
</html>
